<?php

use App\Enums\GameStatus;
use App\Models\Anime;
use App\Models\Character;
use App\Models\Game;

it('suggests matching characters regardless of accents and case', function () {
    $game = Game::factory()->create();
    Character::factory()->named('Satoru Gojō', ['Gojo'])->create();
    Character::factory()->named('Levi')->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->set('search', 'GOJO')
        ->assertSee('Satoru Gojō')
        ->assertDontSee('Levi');
});

it('records a wrong guess and sharpens the image', function () {
    $game = Game::factory()->create();
    $wrong = Character::factory()->named('Levi')->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->call('guess', $wrong->id)
        ->assertSee('Levi')
        ->assertSee('Faux !')
        ->assertSee('Essai 2 / 5')
        ->assertSet('search', '');

    expect($game->fresh()->revealLevel())->toBe(2);
});

it('tells the player when the wrong character comes from the right anime', function () {
    $anime = Anime::factory()->create();
    $game = Game::factory()->for(Character::factory()->for($anime))->create();
    $sameAnime = Character::factory()->for($anime)->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->call('guess', $sameAnime->id)
        ->assertSee('mais c&#039;est le bon anime', false);
});

it('reveals the answer when the game is won', function () {
    $game = Game::factory()->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->call('guess', $game->character_id)
        ->assertSee('Félicitations !')
        ->assertSee($game->character->anime->title)
        ->assertSee('Partager mon résultat');

    expect($game->fresh()->status)->toBe(GameStatus::Won);
});

it('guesses the first suggestion when pressing enter', function () {
    $game = Game::factory()->create();
    $levi = Character::factory()->named('Levi')->create(['favourites' => 10]);

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->set('search', 'lev')
        ->call('guessFirstSuggestion');

    expect($game->fresh()->guesses)->toBe([$levi->id]);
});

it('forbids playing another player\'s game', function () {
    $game = Game::factory()->create();

    playAs(fake()->uuid())
        ->test('game-board', ['gameId' => $game->id])
        ->assertForbidden();
});

it('turns the window close button into a link back to the given page', function () {
    $game = Game::factory()->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id, 'backUrl' => route('animes')])
        ->assertSeeHtml('href="'.route('animes').'"')
        ->assertSeeHtml('aria-label="Retour"');
});

it('plays the celebration animation when the game is won during the visit', function () {
    $game = Game::factory()->create();

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->assertDontSeeHtml('window-celebrate')
        ->call('guess', $game->character_id)
        ->assertSeeHtml('window-celebrate');
});

it('shakes the window like an error when the last attempt fails', function () {
    $game = Game::factory()->create(['guesses' => Character::factory()->count(Game::MAX_ATTEMPTS - 1)->create()->pluck('id')->all()]);

    playAs($game->player_uuid)
        ->test('game-board', ['gameId' => $game->id])
        ->call('guess', Character::factory()->create()->id)
        ->assertSeeHtml('window-error')
        ->assertDontSeeHtml('window-celebrate');
});
