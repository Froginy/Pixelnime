<?php

use App\Enums\Difficulty;
use App\Models\DailyChallenge;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pixelnime — devine le personnage d\'anime')] class extends Component
{
    public bool $choosingDifficulty = false;

    #[Computed]
    public function yesterday(): ?DailyChallenge
    {
        return DailyChallenge::yesterday()?->load('character.anime');
    }
};
?>

<div class="grid grid-cols-12 gap-x-8 gap-y-10">
    <div class="col-span-12 flex flex-col gap-4 lg:col-span-5">
        <h1 class="font-title text-[32px] font-semibold night:text-rose-pale">Hier, c'était :</h1>

        @if ($this->yesterday)
            @php($character = $this->yesterday->character)
            <x-ui.window :title="Str::slug($character->name).'.jpg'">
                <img src="{{ route('daily.image', $this->yesterday) }}" alt="{{ $character->name }}" class="aspect-[2/3] w-full rounded-[10px] bg-lavender object-cover">
                <div>
                    <p class="font-title text-[28px] font-semibold leading-tight">{{ $character->name }}</p>
                    <p class="text-base text-ink-soft">{{ $character->anime->title }}</p>
                </div>
            </x-ui.window>
        @else
            <x-ui.window title="mystere.txt">
                <p class="text-lg">Pas encore de personnage d'hier. Joue le daily d'aujourd'hui et reviens demain pour découvrir la réponse !</p>
            </x-ui.window>
        @endif
    </div>

    <x-ui.window title="Menu" :delay="90" :back-click="$choosingDifficulty ? '$set(\'choosingDifficulty\', false)' : null" class="col-span-12 self-start lg:col-span-6 lg:col-start-7 lg:mt-[60px]" body-class="items-stretch justify-center gap-5 px-6 py-10 sm:px-10">
        @if (! $choosingDifficulty)
            <div wire:key="menu-modes" class="window-swap flex flex-col gap-5">
            <h2 class="text-center font-title text-[26px] font-semibold">Choisis ton mode de jeu</h2>
            <p class="text-center text-base text-ink-soft">Devine le personnage caché derrière l'image pixelisée. 5 essais, et l'image devient plus nette à chaque erreur.</p>

            <x-ui.button :href="route('daily')" size="lg">Daily</x-ui.button>
            <x-ui.button :href="route('animes')" size="lg">Anime</x-ui.button>
            <x-ui.button wire:click="$set('choosingDifficulty', true)" size="lg">Difficulté</x-ui.button>
            </div>
        @else
            <div wire:key="menu-difficulties" class="window-swap flex flex-col gap-5">
            <h2 class="text-center font-title text-[26px] font-semibold">Mode infini : quelle difficulté ?</h2>

            @foreach (Difficulty::cases() as $difficulty)
                <x-ui.button wire:key="difficulty-{{ $difficulty->value }}" :href="route('infinite', ['difficulte' => $difficulty->value])" size="lg">
                    {{ $difficulty->label() }}
                </x-ui.button>
            @endforeach
            <x-ui.button :href="route('infinite')" size="lg">Toutes</x-ui.button>
            </div>
        @endif
    </x-ui.window>
</div>
