<?php

use App\Enums\GameMode;
use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Character;
use App\Models\DailyChallenge;
use App\Models\Game;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Daily — Pixelnime')] class extends Component
{
    #[Locked]
    public ?int $gameId = null;

    public function mount(): void
    {
        if (Character::query()->doesntExist()) {
            return;
        }

        $challenge = DailyChallenge::forDate(today());

        $this->gameId = Game::query()->firstOrCreate(
            [
                'player_uuid' => EnsurePlayerCookie::playerUuid(),
                'daily_challenge_id' => $challenge->id,
            ],
            [
                'mode' => GameMode::Daily,
                'character_id' => $challenge->character_id,
            ],
        )->id;
    }
};
?>

<div class="flex flex-col gap-6">
    <h1 class="font-title text-[32px] font-semibold night:text-rose-pale">Le personnage du jour</h1>

    @if ($gameId)
        <livewire:game-board :game-id="$gameId" :back-url="route('home')" :key="'game-'.$gameId" />
    @else
        <x-ui.window title="Oups.txt" :back="route('home')" class="max-w-xl">
            <p class="text-lg">Aucun personnage n'est encore importé. Lance <code>php artisan pixelnime:import</code> puis recharge la page.</p>
        </x-ui.window>
    @endif
</div>
