<?php

use App\Enums\Difficulty;
use App\Models\Anime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Animes — Pixelnime')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    /** @var list<string> */
    #[Url(as: 'difficulte')]
    public array $difficulties = [];

    /** @var list<int> */
    public array $selected = [];

    /**
     * Get the animes matching the filters, from the easiest (most popular) to the hardest.
     *
     * @return Collection<int, Anime>
     */
    #[Computed]
    public function animes(): Collection
    {
        return Anime::query()
            ->withCount('characters')
            ->has('characters')
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
            ->when($this->difficulties, fn (Builder $query, array $difficulties) => $query->whereIn('difficulty', $difficulties))
            ->orderByDesc('popularity')
            ->orderBy('id')
            ->get();
    }

    public function toggleDifficulty(string $difficulty): void
    {
        if (! Difficulty::tryFrom($difficulty)) {
            return;
        }

        $this->difficulties = in_array($difficulty, $this->difficulties, true)
            ? array_values(array_diff($this->difficulties, [$difficulty]))
            : [...$this->difficulties, $difficulty];
    }

    public function toggleAnime(int $animeId): void
    {
        $this->selected = in_array($animeId, $this->selected, true)
            ? array_values(array_diff($this->selected, [$animeId]))
            : [...$this->selected, $animeId];
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'difficulties', 'selected');
    }

    public function play(): void
    {
        $animeIds = Anime::query()->whereIn('id', $this->selected)->pluck('id')->all();

        if ($animeIds === []) {
            $this->addError('selected', 'Sélectionne au moins un anime.');

            return;
        }

        $this->redirectRoute('infinite', ['animes' => $animeIds], navigate: true);
    }
};
?>

<div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 flex flex-col gap-6 lg:col-span-9">
        <h1 class="font-title text-[32px] font-semibold night:text-rose-pale">Choisis tes animes</h1>

        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($this->animes as $anime)
                <x-ui.window
                    wire:key="anime-{{ $anime->id }}"
                    :title="Str::slug($anime->title).'.jpg'"
                    controls="close"
                    body-class="p-3 gap-2.5"
                    @class(['outline-4 outline-offset-2 outline-violet' => in_array($anime->id, $selected, true)])
                >
                    <button
                        type="button"
                        wire:click="toggleAnime({{ $anime->id }})"
                        aria-pressed="{{ in_array($anime->id, $selected, true) ? 'true' : 'false' }}"
                        class="flex flex-1 flex-col gap-2.5 rounded-control text-left focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-violet"
                    >
                        @if ($anime->coverUrl())
                            <img src="{{ $anime->coverUrl() }}" alt="" class="aspect-[3/4] w-full rounded-lg object-cover" loading="lazy">
                        @else
                            <span class="aspect-[3/4] w-full rounded-lg bg-lavender"></span>
                        @endif

                        <span class="line-clamp-2 text-base">{{ $anime->title }}</span>

                        <span class="flex flex-wrap items-center gap-2 text-sm">
                            <span @class([
                                'rounded-bubble px-2.5 py-0.5',
                                'bg-mint text-success' => $anime->difficulty === Difficulty::Easy,
                                'bg-lavender text-ink' => $anime->difficulty === Difficulty::Medium,
                                'bg-danger-pale text-danger' => $anime->difficulty === Difficulty::Hard,
                            ])>{{ $anime->difficulty->label() }}</span>
                            <span class="text-ink-soft">{{ $anime->characters_count }} perso{{ $anime->characters_count > 1 ? 's' : '' }}</span>
                        </span>
                    </button>
                </x-ui.window>
            @empty
                <x-ui.window title="Oups.txt" class="sm:col-span-2 xl:col-span-3">
                    <p class="text-lg">Aucun anime ne correspond à ces filtres.</p>
                </x-ui.window>
            @endforelse
        </div>
    </div>

    <x-ui.window title="Filtres" :back="route('home')" class="col-span-12 self-start lg:sticky lg:top-6 lg:col-span-3" body-class="p-4 gap-5">
        <div>
            <label for="anime-search" class="sr-only">Rechercher un anime</label>
            <input
                id="anime-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Rechercher…"
                class="w-full rounded-inner border-2 border-rose bg-white px-4 py-2.5 text-lg placeholder:text-ink-soft focus:border-violet focus:outline-none"
            >
        </div>

        <fieldset class="flex flex-col gap-2.5">
            <legend class="mb-2.5 font-title text-xl font-semibold">Difficulté</legend>
            <div class="flex flex-wrap gap-2">
                @foreach (Difficulty::cases() as $difficulty)
                    <x-ui.chip
                        wire:key="difficulty-{{ $difficulty->value }}"
                        wire:click="toggleDifficulty('{{ $difficulty->value }}')"
                        :active="in_array($difficulty->value, $difficulties, true)"
                    >{{ $difficulty->label() }}</x-ui.chip>
                @endforeach
            </div>
        </fieldset>

        <p class="text-base text-ink-soft" aria-live="polite">
            {{ count($selected) }} anime{{ count($selected) > 1 ? 's' : '' }} sélectionné{{ count($selected) > 1 ? 's' : '' }}
        </p>

        @error('selected')
            <p class="text-sm text-danger">{{ $message }}</p>
        @enderror

        <div class="flex flex-col gap-3">
            <x-ui.button wire:click="play" class="px-3" :disabled="$selected === []">Jouer</x-ui.button>
            <x-ui.button wire:click="resetFilters" size="sm">Réinitialiser</x-ui.button>
        </div>
    </x-ui.window>
</div>
