<?php

use App\Enums\Difficulty;
use App\Enums\GameMode;
use App\Models\Anime;
use App\Models\Character;
use App\Models\Game;

it('starts a game on a character of the chosen difficulty', function () {
    Character::factory()->difficulty(Difficulty::Easy)->count(3)->create();
    $hard = Character::factory()->difficulty(Difficulty::Hard)->create();

    playAs(fake()->uuid())->withQueryParams(['difficulte' => 'hard'])->test('pages::infinite');

    expect(Game::query()->sole())
        ->mode->toBe(GameMode::Infinite)
        ->character_id->toBe($hard->id);
});

it('only picks characters from the selected animes', function () {
    Character::factory()->count(3)->create();
    $anime = Anime::factory()->create();
    $chosen = Character::factory()->for($anime)->create();

    playAs(fake()->uuid())->withQueryParams(['animes' => [$anime->id]])->test('pages::infinite');

    expect(Game::query()->sole()->character_id)->toBe($chosen->id);
});

it('resumes the unfinished game instead of starting a new one', function () {
    $game = Game::factory()->create();

    playAs($game->player_uuid)->test('pages::infinite')->assertSet('gameId', $game->id);
});

it('does not repeat the characters the player just played when asking for the next one', function () {
    $player = fake()->uuid();
    $finished = Game::factory()->forPlayer($player)->won()->create();
    Character::factory()->count(2)->create();

    $component = playAs($player)->test('pages::infinite');
    $firstCharacterId = Game::query()->findOrFail($component->get('gameId'))->character_id;

    $component->call('nextGame');

    expect(Game::query()->findOrFail($component->get('gameId'))->character_id)
        ->not->toBeIn([$finished->character_id, $firstCharacterId]);
});

it('counts the infinite games won in a row', function () {
    $player = fake()->uuid();
    Game::factory()->forPlayer($player)->won()->create();
    Game::factory()->forPlayer($player)->lost()->create();
    Game::factory()->forPlayer($player)->won()->count(2)->create();

    playAs($player)->test('pages::infinite')->assertSee('Série : 2');
});

it('says so when no character matches the filters', function () {
    Character::factory()->difficulty(Difficulty::Easy)->create();

    playAs(fake()->uuid())
        ->withQueryParams(['difficulte' => 'hard'])
        ->test('pages::infinite')
        ->assertSee('Aucun personnage ne correspond');
});

it('goes back to the anime list only when playing a selection of animes', function () {
    $anime = Anime::factory()->create();
    Character::factory()->for($anime)->create();

    $withoutSelection = playAs(fake()->uuid())->test('pages::infinite');
    $withSelection = playAs(fake()->uuid())->withQueryParams(['animes' => [$anime->id]])->test('pages::infinite');

    expect($withSelection->instance()->backUrl())->toBe(route('animes'))
        ->and($withoutSelection->instance()->backUrl())->toBe(route('home'));
});
