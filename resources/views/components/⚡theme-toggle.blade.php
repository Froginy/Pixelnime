<?php

use Livewire\Component;

new class extends Component
{
    public function toggle(): void
    {
        session(['theme' => $this->isNight() ? 'day' : 'night']);

        $this->redirect(url()->previous());
    }

    public function isNight(): bool
    {
        return session('theme') === 'night';
    }
};
?>

<div>
    <x-ui.button wire:click="toggle" size="sm" class="sm:px-6 sm:py-2.5 sm:text-xl">
        {{ $this->isNight() ? 'Mode jour' : 'Mode nuit' }}
    </x-ui.button>
</div>
