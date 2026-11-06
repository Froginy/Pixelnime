<?php

use App\Enums\GameMode;
use App\Models\Character;
use App\Models\DailyChallenge;
use App\Models\Game;

it('starts the daily game on today\'s character', function () {
    Character::factory()->count(3)->create();
    $player = fake()->uuid();

    playAs($player)->test('pages::daily')->assertSee('Qui se cache derrière l');

    $game = Game::query()->sole();
    expect($game)
        ->player_uuid->toBe($player)
        ->mode->toBe(GameMode::Daily)
        ->character_id->toBe(DailyChallenge::forDate(today())->character_id);
});

it('resumes the same daily game when the player comes back', function () {
    Character::factory()->count(3)->create();
    $player = fake()->uuid();

    $first = playAs($player)->test('pages::daily')->get('gameId');
    $second = playAs($player)->test('pages::daily')->get('gameId');

    expect($second)->toBe($first)
        ->and(Game::query()->count())->toBe(1);
});

it('explains how to import characters when there are none', function () {
    playAs(fake()->uuid())->test('pages::daily')->assertSee('pixelnime:import');
});
