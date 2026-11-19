<?php

namespace App\Models;

use App\Enums\Difficulty;
use Database\Factories\CharacterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $anilist_id
 * @property int $anime_id
 * @property string $name
 * @property string $search_name
 * @property list<string> $alternative_names
 * @property string $image_path
 * @property int $favourites
 * @property Difficulty $difficulty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Anime $anime
 */
#[Fillable(['anilist_id', 'anime_id', 'name', 'search_name', 'alternative_names', 'image_path', 'favourites', 'difficulty'])]
class Character extends Model
{
    /** @use HasFactory<CharacterFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alternative_names' => 'array',
            'favourites' => 'integer',
            'difficulty' => Difficulty::class,
        ];
    }

    /**
     * @return BelongsTo<Anime, $this>
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Normalize a name for accent and case insensitive searching.
     */
    public static function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->lower()->squish()->toString();
    }

    /**
     * Build the searchable text from the main and alternative names.
     *
     * @param  list<string>  $alternativeNames
     */
    public static function searchableText(string $name, array $alternativeNames): string
    {
        return collect([$name, ...$alternativeNames])
            ->map(fn (string $value): string => static::normalizeName($value))
            ->filter()
            ->unique()
            ->implode(' | ');
    }

    /**
     * Split a search term in normalized words, ignoring accents, case and punctuation.
     *
     * @return list<string>
     */
    public static function searchWords(string $term): array
    {
        $normalized = Str::of($term)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();

        return $normalized === '' ? [] : explode(' ', $normalized);
    }

    /**
     * Scope the query to characters whose name or one of its aliases contains every word of the term, in any order.
     *
     * @param  Builder<Character>  $query
     */
    public function scopeMatching(Builder $query, string $term): void
    {
        $words = static::searchWords($term);

        if ($words === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        foreach ($words as $word) {
            $query->where('search_name', 'like', "%{$word}%");
        }
    }

    /**
     * Scope the query to the characters of the animes whose title contains every word of the term.
     *
     * @param  Builder<Character>  $query
     */
    public function scopeFromAnimeMatching(Builder $query, string $term): void
    {
        $words = static::searchWords($term);

        if ($words === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('anime', function (Builder $animeQuery) use ($words): void {
            foreach ($words as $word) {
                $animeQuery->where('title', 'like', "%{$word}%");
            }
        });
    }

    /**
     * Get the alias that matches the term when the main name does not, to explain the suggestion.
     */
    public function matchingAlias(string $term): ?string
    {
        $words = static::searchWords($term);
        $contains = fn (string $value): bool => $words !== [] && collect($words)->every(
            fn (string $word): bool => str_contains(implode(' ', static::searchWords($value)), $word)
        );

        if ($contains($this->name)) {
            return null;
        }

        return collect($this->alternative_names)->first(fn (string $alias): bool => $contains($alias));
    }

    /**
     * Get the initials of the character, used as a hint.
     */
    public function initials(): string
    {
        return collect(explode(' ', Str::squish($this->name)))
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)).'.')
            ->implode(' ');
    }
}
