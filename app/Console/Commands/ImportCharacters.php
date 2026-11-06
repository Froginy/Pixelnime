<?php

namespace App\Console\Commands;

use App\Enums\Difficulty;
use App\Models\Anime;
use App\Models\Character;
use App\Services\AniListClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Throwable;

#[Signature('pixelnime:import
    {--limit=300 : Nombre de personnages populaires qui déterminent les animes importés}
    {--per-anime=80 : Nombre maximum de personnages importés pour chaque anime (0 pour ne pas compléter les animes)}
    {--min-favourites=10 : Popularité minimale (favoris AniList) d\'un personnage secondaire}
    {--anime=* : Importe seulement ces animes (titre à chercher sur AniList) avec leurs personnages}')]
#[Description('Importe les personnages d\'anime les plus populaires depuis AniList, puis complète chaque anime avec ses personnages')]
class ImportCharacters extends Command
{
    /**
     * Pause between two AniList API calls, to stay under its rate limit.
     */
    private const int PAUSE_MILLISECONDS = 2100;

    /**
     * Execute the console command.
     */
    public function handle(AniListClient $aniList): int
    {
        $perAnime = max(0, (int) $this->option('per-anime'));
        $minFavourites = max(0, (int) $this->option('min-favourites'));
        $titles = $this->option('anime');

        if ($titles !== []) {
            $animes = collect($titles)->filter()->map(fn (string $title): ?Anime => $this->findAnime($aniList, $title))->filter();
        } else {
            $this->importPopularCharacters($aniList, max(1, (int) $this->option('limit')));
            $animes = Anime::query()->orderByDesc('popularity')->get();
        }

        if ($perAnime > 0) {
            $this->completeAnimes($aniList, $animes, $perAnime, $minFavourites);
        }

        $this->assignDifficulties(Character::query()->orderByDesc('favourites'));
        $this->assignDifficulties(Anime::query()->orderByDesc('popularity'));

        $this->info(Character::query()->count().' personnages et '.Anime::query()->count().' animes en base.');

        return self::SUCCESS;
    }

    /**
     * Import the most favourited characters, which decides which animes are in the game.
     */
    private function importPopularCharacters(AniListClient $aniList, int $limit): void
    {
        $this->info("Import des {$limit} personnages les plus populaires…");

        $imported = 0;
        $page = 1;

        do {
            $result = $aniList->popularCharacters($page);

            foreach ($result['characters'] as $data) {
                if ($imported >= $limit) {
                    break;
                }

                $media = $data['media']['nodes'][0] ?? null;

                if ($media === null) {
                    continue;
                }

                try {
                    if ($this->storeCharacter($aniList, $data, $this->storeAnime($aniList, $media))) {
                        $imported++;
                    }
                } catch (Throwable $exception) {
                    $this->warn("Personnage {$data['id']} ignoré : {$exception->getMessage()}");
                }
            }

            $page++;

            if ($result['hasNextPage'] && $imported < $limit) {
                Sleep::for(self::PAUSE_MILLISECONDS)->milliseconds();
            }
        } while ($result['hasNextPage'] && $imported < $limit);
    }

    private function findAnime(AniListClient $aniList, string $title): ?Anime
    {
        $media = $aniList->searchAnime($title);

        if ($media === null) {
            $this->warn("Aucun anime trouvé pour « {$title} ».");

            return null;
        }

        $anime = $this->storeAnime($aniList, $media);
        $this->info("« {$title} » → {$anime->title}");

        return $anime;
    }

    /**
     * Add the main and supporting characters of the given animes, the most popular first.
     *
     * @param  Collection<int, Anime>  $animes
     */
    private function completeAnimes(AniListClient $aniList, Collection $animes, int $perAnime, int $minFavourites): void
    {
        $this->info("Ajout des personnages de {$animes->count()} animes…");

        foreach ($animes as $anime) {
            $seen = 0;
            $page = 1;

            do {
                Sleep::for(self::PAUSE_MILLISECONDS)->milliseconds();

                $result = $aniList->animeCharacters($anime->anilist_id, $page);
                $reachedUnpopular = false;

                foreach ($result['characters'] as $edge) {
                    $data = $edge['node'];

                    if (($data['favourites'] ?? 0) < $minFavourites || $seen >= $perAnime) {
                        $reachedUnpopular = true;

                        break;
                    }

                    if ($edge['role'] === 'BACKGROUND') {
                        continue;
                    }

                    $seen++;

                    try {
                        $this->storeCharacter($aniList, $data, $anime);
                    } catch (Throwable $exception) {
                        $this->warn("Personnage {$data['id']} ignoré : {$exception->getMessage()}");
                    }
                }

                $page++;
            } while ($result['hasNextPage'] && ! $reachedUnpopular);

            $this->line("  {$anime->title} : {$anime->characters()->count()} personnages");
        }
    }

    /**
     * @param  array<string, mixed>  $media
     */
    private function storeAnime(AniListClient $aniList, array $media): Anime
    {
        $anime = Anime::query()->firstOrNew(['anilist_id' => $media['id']]);
        $anime->fill([
            'title' => $media['title']['english'] ?? $media['title']['romaji'],
            'popularity' => $media['popularity'] ?? 0,
        ]);
        $anime->difficulty ??= Difficulty::Hard;

        if ($anime->cover_path === null && ($coverUrl = $media['coverImage']['large'] ?? null)) {
            $anime->cover_path = $this->storeImage($aniList, 'public', 'covers', $media['id'], $coverUrl);
        }

        $anime->save();

        return $anime;
    }

    /**
     * Store a character, skipping the ones without a real picture.
     *
     * A character that already exists keeps its anime, which is the most popular one it appears in.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeCharacter(AniListClient $aniList, array $data, Anime $anime): bool
    {
        $imageUrl = $data['image']['large'] ?? null;
        $name = $data['name']['full'] ?? null;

        if ($imageUrl === null || $name === null || str_ends_with($imageUrl, '/default.jpg')) {
            return false;
        }

        $alternativeNames = array_values(array_filter($data['name']['alternative'] ?? [], fn (?string $value): bool => filled($value)));

        $character = Character::query()->firstOrNew(['anilist_id' => $data['id']]);
        $character->anime_id ??= $anime->id;
        $character->fill([
            'name' => $name,
            'alternative_names' => $alternativeNames,
            'search_name' => Character::searchableText($name, $alternativeNames),
            'favourites' => $data['favourites'] ?? 0,
        ]);
        $character->difficulty ??= Difficulty::Hard;

        if (! $character->image_path || ! Storage::disk('local')->exists($character->image_path)) {
            $character->image_path = $this->storeImage($aniList, 'local', 'characters', $data['id'], $imageUrl);
        }

        $character->save();

        return true;
    }

    private function storeImage(AniListClient $aniList, string $disk, string $directory, int $anilistId, string $url): string
    {
        $extension = pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $path = "{$directory}/{$anilistId}.{$extension}";

        Storage::disk($disk)->put($path, $aniList->downloadImage($url));

        return $path;
    }

    /**
     * Split the records, ordered from the most to the least popular, in difficulty tiers.
     *
     * @param  Builder<covariant Model>  $query
     */
    private function assignDifficulties(Builder $query): void
    {
        $ids = $query->orderBy('id')->pluck('id');
        $total = $ids->count();
        $model = $query->getModel();

        $ids->values()
            ->groupBy(fn (int $id, int $rank): string => Difficulty::fromRank($rank, $total)->value)
            ->each(fn ($group, string $difficulty) => $model->newQuery()->whereIn('id', $group)->update(['difficulty' => $difficulty]));
    }
}
