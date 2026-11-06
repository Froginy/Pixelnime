<?php

use App\Enums\GameMode;
use App\Enums\GameStatus;
use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Character;
use App\Models\Game;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $gameId;

    #[Locked]
    public string $backUrl = '/';

    public string $search = '';

    #[Computed]
    public function game(): Game
    {
        $game = Game::query()->with('character.anime')->findOrFail($this->gameId);

        abort_unless($game->belongsToPlayer(EnsurePlayerCookie::playerUuid()), 403);

        return $game;
    }

    /**
     * @return Collection<int, Character>
     */
    #[Computed]
    public function suggestions(): Collection
    {
        if ($this->game->status->isFinished() || mb_strlen(trim($this->search)) < 2) {
            return collect();
        }

        return Character::query()
            ->with('anime')
            ->matching($this->search)
            ->whereNotIn('id', $this->game->guesses)
            ->orderByDesc('favourites')
            ->limit(8)
            ->get();
    }

    /**
     * Get each guess with the answer the game gives to it.
     *
     * @return Collection<int, array{character: Character, result: string}>
     */
    #[Computed]
    public function messages(): Collection
    {
        $target = $this->game->character;

        return $this->game->guessedCharacters()->map(fn (Character $character): array => [
            'character' => $character,
            'result' => match (true) {
                $character->id === $target->id => 'right',
                $character->anime_id === $target->anime_id => 'close',
                default => 'wrong',
            },
        ]);
    }

    public function guess(int $characterId): void
    {
        $character = Character::query()->find($characterId);

        if (! $character || ! $this->game->canGuess($character)) {
            $this->addError('search', 'Ce personnage ne peut pas être proposé.');

            return;
        }

        $this->game->submitGuess($character);

        $this->reset('search');
        unset($this->game, $this->messages, $this->suggestions);
    }

    public function guessFirstSuggestion(): void
    {
        $first = $this->suggestions->first();

        if ($first) {
            $this->guess($first->id);
        }
    }

    public function shareText(): string
    {
        $squares = $this->messages
            ->map(fn (array $message): string => match ($message['result']) {
                'right' => '🟩',
                'close' => '🟧',
                default => '🟥',
            })
            ->implode('');

        $score = $this->game->status === GameStatus::Won ? count($this->game->guesses) : 'X';
        $label = $this->game->mode === GameMode::Daily
            ? 'Pixelnime '.$this->game->dailyChallenge?->date->format('d/m')
            : 'Pixelnime infini';

        return "{$label} — {$score}/".Game::MAX_ATTEMPTS."\n{$squares}";
    }

    public function timeUntilNextDaily(): string
    {
        $minutes = (int) now()->diffInMinutes(today()->addDay());

        return intdiv($minutes, 60).' h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).' min';
    }
};
?>

@php
    $game = $this->game;
    $character = $game->character;
    $level = $game->revealLevel();
    $finished = $game->status->isFinished();
@endphp

<div class="grid grid-cols-12 gap-x-8 gap-y-10">
    <x-ui.window
        :title="$finished ? Str::slug($character->name).'.jpg' : 'personnage.jpg'"
        :back="$backUrl"
        class="col-span-12 lg:col-span-5"
    >
        <img
            wire:key="image-{{ $game->id }}-{{ $level }}"
            src="{{ route('games.image', ['game' => $game, 'v' => $level]) }}"
            alt="{{ $finished ? $character->name : 'Personnage mystère, netteté '.$level.' sur '.Game::FULL_QUALITY_LEVEL }}"
            class="aspect-[2/3] w-full rounded-[10px] bg-lavender object-cover [image-rendering:pixelated]"
        >

        @if ($finished)
            <div>
                <p class="font-title text-[28px] font-semibold leading-tight">{{ $character->name }}</p>
                <p class="text-base text-ink-soft">{{ $character->anime->title }}</p>
            </div>
        @else
            <div class="flex items-center justify-between gap-4">
                <p class="text-base text-ink-soft">Essai {{ count($game->guesses) + 1 }} / {{ Game::MAX_ATTEMPTS }}</p>
                <div class="flex gap-1.5" aria-hidden="true">
                    @for ($attempt = 1; $attempt <= Game::MAX_ATTEMPTS; $attempt++)
                        <span @class(['size-3 rounded-pixel', 'bg-rose' => $attempt <= count($game->guesses), 'bg-lavender' => $attempt > count($game->guesses)])></span>
                    @endfor
                </div>
            </div>
        @endif
    </x-ui.window>

    <x-ui.window title="Chatbox" :back="$backUrl" class="col-span-12 min-h-[32rem] lg:col-span-6 lg:col-start-7">
        <div class="flex flex-1 flex-col gap-3" aria-live="polite">
            <x-ui.bubble type="info">Qui se cache derrière l'image ?</x-ui.bubble>

            @foreach ($this->messages as $message)
                <div wire:key="message-{{ $message['character']->id }}" class="flex flex-col gap-3">
                    <x-ui.bubble type="player">{{ $message['character']->name }}</x-ui.bubble>
                    <x-ui.bubble :type="$message['result']">
                        {{ match ($message['result']) {
                            'right' => 'Félicitations !',
                            'close' => 'Faux… mais c\'est le bon anime !',
                            default => 'Faux !',
                        } }}
                    </x-ui.bubble>
                </div>
            @endforeach

            @if ($game->status === GameStatus::Lost)
                <x-ui.bubble type="info">Perdu ! C'était {{ $character->name }} ({{ $character->anime->title }}).</x-ui.bubble>
            @endif
        </div>

        @if (! $finished)
            <div class="relative">
                <label for="guess-{{ $game->id }}" class="sr-only">Nom du personnage</label>
                <div class="flex items-center gap-2 rounded-inner border-2 border-rose bg-white py-1.5 pr-1.5 pl-4 focus-within:border-violet">
                    <input
                        id="guess-{{ $game->id }}"
                        type="text"
                        wire:model.live.debounce.250ms="search"
                        wire:keydown.enter="guessFirstSuggestion"
                        placeholder="Qui est-ce ?"
                        autocomplete="off"
                        class="min-w-0 flex-1 bg-transparent py-1.5 text-lg text-ink placeholder:text-ink-soft focus:outline-none"
                    >
                    <button
                        type="button"
                        wire:click="guessFirstSuggestion"
                        class="grid size-10 shrink-0 place-items-center rounded-control bg-violet transition hover:bg-ink focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet"
                        aria-label="Proposer"
                    >
                        <svg class="size-4 fill-white" viewBox="0 0 15 15" aria-hidden="true">
                            @foreach ([[0, 2], [1, 2], [2, 2], [3, 2], [4, 2], [2, 0], [3, 1], [3, 3], [2, 4]] as [$column, $row])
                                <rect x="{{ $column * 3 }}" y="{{ $row * 3 }}" width="3" height="3"/>
                            @endforeach
                        </svg>
                    </button>
                </div>

                @error('search')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror

                @if ($this->suggestions->isNotEmpty())
                    <ul class="absolute inset-x-0 bottom-full z-10 mb-2 max-h-80 overflow-y-auto rounded-inner border-2 border-rose bg-white p-1.5 shadow-window">
                        @foreach ($this->suggestions as $suggestion)
                            <li wire:key="suggestion-{{ $suggestion->id }}">
                                <button
                                    type="button"
                                    wire:click="guess({{ $suggestion->id }})"
                                    class="flex w-full flex-col items-start rounded-control px-3 py-2 text-left hover:bg-lavender focus-visible:bg-lavender focus-visible:outline-none"
                                >
                                    <span class="text-base text-ink">{{ $suggestion->name }}</span>
                                    <span class="text-sm text-ink-soft">{{ $suggestion->anime->title }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @elseif (mb_strlen(trim($search)) >= 2)
                    <p class="mt-2 text-sm text-ink-soft">Aucun personnage trouvé.</p>
                @endif
            </div>
        @else
            <div class="flex flex-col gap-3">
                @if ($game->mode === GameMode::Daily)
                    <p wire:poll.60s class="text-center text-base text-ink-soft">
                        Prochain personnage dans {{ $this->timeUntilNextDaily() }}
                    </p>
                @endif

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-ui.button
                        class="flex-1"
                        x-data="{ copied: false }"
                        x-on:click="navigator.clipboard.writeText(@js($this->shareText())); copied = true; setTimeout(() => copied = false, 2000)"
                    >
                        <span x-text="copied ? 'Copié !' : 'Partager mon résultat'">Partager mon résultat</span>
                    </x-ui.button>

                    @if ($game->mode === GameMode::Infinite)
                        <x-ui.button class="flex-1" wire:click="$parent.nextGame()">Personnage suivant</x-ui.button>
                    @endif
                </div>
            </div>
        @endif
    </x-ui.window>
</div>
