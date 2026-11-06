<?php

use App\Enums\GameStatus;
use App\Models\Anime;
use App\Models\Character;
use App\Models\Game;

it('wins and reveals the full image when the right character is guessed', function () {
    $game = Game::factory()->create();

    $game->submitGuess($game->character);

    expect($game->fresh())
        ->status->toBe(GameStatus::Won)
        ->revealLevel()->toBe(Game::FULL_QUALITY_LEVEL);
});

it('reveals one more quality level after each wrong guess', function () {
    $game = Game::factory()->create();

    expect($game->revealLevel())->toBe(1);

    $game->submitGuess(Character::factory()->create());
    $game->submitGuess(Character::factory()->create());

    expect($game->fresh())
        ->status->toBe(GameStatus::Playing)
        ->revealLevel()->toBe(3)
        ->remainingAttempts()->toBe(3);
});

it('loses after five wrong guesses', function () {
    $game = Game::factory()->create();

    Character::factory()->count(Game::MAX_ATTEMPTS)->create()->each(fn (Character $character) => $game->submitGuess($character));

    expect($game->fresh())
        ->status->toBe(GameStatus::Lost)
        ->revealLevel()->toBe(Game::FULL_QUALITY_LEVEL);
});

it('rejects a character that was already guessed', function () {
    $game = Game::factory()->create();
    $character = Character::factory()->create();
    $game->submitGuess($character);

    expect($game->canGuess($character))->toBeFalse()
        ->and(fn () => $game->submitGuess($character))->toThrow(LogicException::class);
});

it('rejects guesses once the game is over', function () {
    $game = Game::factory()->won()->create();

    expect(fn () => $game->submitGuess(Character::factory()->create()))->toThrow(LogicException::class);
});

it('returns the guessed characters in submission order', function () {
    $game = Game::factory()->create();
    $characters = Character::factory()->count(3)->for(Anime::factory())->create()->reverse()->values();

    $characters->each(fn (Character $character) => $game->submitGuess($character));

    expect($game->guessedCharacters()->pluck('id')->all())->toBe($characters->pluck('id')->all());
});
