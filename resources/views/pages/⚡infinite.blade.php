<?php

use App\Enums\Difficulty;
use App\Enums\GameMode;
use App\Enums\GameStatus;
use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Anime;
use App\Models\Character;
use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Mode infini — Pixelnime')] class extends Component
{
    #[Url(as: 'difficulte')]
    public ?string $difficulty = null;

    /** @var list<int> */
    #[Url]
    public array $animes = [];

    #[Locked]
    public ?int $gameId = null;

    public function mount(): void
    {
        $this->animes = array_values(array_map('intval', $this->animes));

        $this->gameId = $this->resumableGame()?->id ?? $this->startGame()?->id;
    }

    /**
     * Go back to the anime list when the player came from there, or to the home page.
     */
    public function backUrl(): string
    {
        return $this->animes === [] ? route('home') : route('animes');
    }

    public function nextGame(): void
    {
        $this->gameId = $this->startGame()?->id;
        unset($this->streak);
    }

    #[Computed]
    public function selectedDifficulty(): ?Difficulty
    {
        return Difficulty::tryFrom((string) $this->difficulty);
    }

    /**
     * @return Collection<int, Anime>
     */
    #[Computed]
    public function selectedAnimes(): Collection
    {
        return Anime::query()->whereIn('id', $this->animes)->orderByDesc('popularity')->get();
    }

    /**
     * Count the infinite games won in a row by the player.
     */
    #[Computed]
    public function streak(): int
    {
        $statuses = Game::query()
            ->where('player_uuid', EnsurePlayerCookie::playerUuid())
            ->where('mode', GameMode::Infinite)
            ->where('status', '!=', GameStatus::Playing)
            ->latest('id')
            ->limit(500)
            ->pluck('status');

        $firstLoss = $statuses->search(fn (GameStatus $status): bool => $status !== GameStatus::Won);

        return $firstLoss === false ? $statuses->count() : $firstLoss;
    }

    /**
     * @return Builder<Character>
     */
    private function filteredCharacters(): Builder
    {
        return Character::query()
            ->when($this->selectedDifficulty, fn (Builder $query, Difficulty $difficulty) => $query->where('difficulty', $difficulty))
            ->when($this->animes, fn (Builder $query, array $animeIds) => $query->whereIn('anime_id', $animeIds));
    }

    /**
     * Get the player's unfinished infinite game if it still matches the filters.
     */
    private function resumableGame(): ?Game
    {
        $game = Game::query()
            ->where('player_uuid', EnsurePlayerCookie::playerUuid())
            ->where('mode', GameMode::Infinite)
            ->where('status', GameStatus::Playing)
            ->latest('id')
            ->first();

        return $game && $this->filteredCharacters()->whereKey($game->character_id)->exists() ? $game : null;
    }

    /**
     * Start a game on a random character matching the filters, avoiding the latest ones played.
     */
    private function startGame(): ?Game
    {
        $playerUuid = EnsurePlayerCookie::playerUuid();

        $recentCharacterIds = Game::query()
            ->where('player_uuid', $playerUuid)
            ->latest('id')
            ->limit(30)
            ->pluck('character_id');

        $characterId = $this->filteredCharacters()->whereNotIn('id', $recentCharacterIds)->inRandomOrder()->value('id')
            ?? $this->filteredCharacters()->inRandomOrder()->value('id');

        if ($characterId === null) {
            return null;
        }

        return Game::query()->create([
            'player_uuid' => $playerUuid,
            'mode' => GameMode::Infinite,
            'character_id' => $characterId,
        ]);
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-col gap-2">
            <h1 class="font-title text-[32px] font-semibold night:text-rose-pale">Mode infini</h1>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="rounded-bubble bg-rose-pale px-3 py-1">
                    {{ $this->selectedDifficulty?->label() ?? 'Toutes difficultés' }}
                </span>
                @forelse ($this->selectedAnimes as $anime)
                    <span wire:key="anime-{{ $anime->id }}" class="rounded-bubble bg-rose-pale px-3 py-1">{{ $anime->title }}</span>
                @empty
                    <span class="rounded-bubble bg-rose-pale px-3 py-1">Tous les animes</span>
                @endforelse
                <a href="{{ route('animes') }}" wire:navigate class="rounded-bubble px-2 py-1 text-ink underline night:text-rose-pale">Changer</a>
            </div>
        </div>

        <p class="rounded-button bg-rose-pale px-4 py-2 font-title text-xl font-semibold">Série : {{ $this->streak }}</p>
    </div>

    @if ($gameId)
        <livewire:game-board :game-id="$gameId" :back-url="$this->backUrl()" :key="'game-'.$gameId" />
    @else
        <x-ui.window title="Oups.txt" :back="$this->backUrl()" class="max-w-xl">
            <p class="text-lg">Aucun personnage ne correspond à ces filtres.</p>
            <x-ui.button :href="route('animes')">Changer les filtres</x-ui.button>
        </x-ui.window>
    @endif
</div>
