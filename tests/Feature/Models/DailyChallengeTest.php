<?php

use App\Enums\Difficulty;
use App\Models\Character;
use App\Models\DailyChallenge;

it('gives everyone the same character on the same day', function () {
    Character::factory()->count(10)->create();

    $first = DailyChallenge::forDate(today());
    $second = DailyChallenge::forDate(today());

    expect($second->id)->toBe($first->id)
        ->and(DailyChallenge::query()->count())->toBe(1);
});

it('picks an easy character first', function () {
    Character::factory()->difficulty(Difficulty::Medium)->count(5)->create();
    $easy = Character::factory()->difficulty(Difficulty::Easy)->create();

    expect(DailyChallenge::forDate(today())->character_id)->toBe($easy->id);
});

it('falls back to a medium character when every easy one was used during the last year', function () {
    $easy = Character::factory()->difficulty(Difficulty::Easy)->create();
    Character::factory()->difficulty(Difficulty::Hard)->count(5)->create();
    $medium = Character::factory()->difficulty(Difficulty::Medium)->create();
    DailyChallenge::factory()->for($easy)->create(['date' => today()->subDay()]);

    expect(DailyChallenge::forDate(today())->character_id)->toBe($medium->id);
});

it('does not reuse a character picked during the last year', function () {
    [$used, $fresh] = Character::factory()->count(2)->create();
    DailyChallenge::factory()->for($used)->create(['date' => today()->subMonths(6)]);

    expect(DailyChallenge::forDate(today())->character_id)->toBe($fresh->id);
});

it('fails clearly when no character was imported', function () {
    expect(fn () => DailyChallenge::forDate(today()))->toThrow(RuntimeException::class, 'pixelnime:import');
});
