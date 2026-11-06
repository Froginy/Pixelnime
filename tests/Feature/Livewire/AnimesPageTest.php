<?php

use App\Enums\Difficulty;
use App\Models\Anime;
use App\Models\Character;

it('lists animes from the most to the least popular', function () {
    $niche = Anime::factory()->has(Character::factory())->create(['title' => 'Niche Anime', 'popularity' => 10]);
    $famous = Anime::factory()->has(Character::factory())->create(['title' => 'Famous Anime', 'popularity' => 9000]);
    Anime::factory()->create(['title' => 'Empty Anime']);

    playAs(fake()->uuid())
        ->test('pages::animes')
        ->assertSeeInOrder(['Famous Anime', 'Niche Anime'])
        ->assertDontSee('Empty Anime');
});

it('filters animes by difficulty and title', function () {
    Anime::factory()->difficulty(Difficulty::Easy)->has(Character::factory())->create(['title' => 'Easy One']);
    Anime::factory()->difficulty(Difficulty::Hard)->has(Character::factory())->create(['title' => 'Hard One']);
    Anime::factory()->difficulty(Difficulty::Hard)->has(Character::factory())->create(['title' => 'Hard Two']);

    playAs(fake()->uuid())
        ->test('pages::animes')
        ->call('toggleDifficulty', 'hard')
        ->assertDontSee('Easy One')
        ->assertSee('Hard One')
        ->set('search', 'two')
        ->assertDontSee('Hard One')
        ->assertSee('Hard Two');
});

it('starts the infinite mode with the selected animes', function () {
    $anime = Anime::factory()->has(Character::factory())->create();

    playAs(fake()->uuid())
        ->test('pages::animes')
        ->call('toggleAnime', $anime->id)
        ->call('play')
        ->assertRedirect(route('infinite', ['animes' => [$anime->id]]));
});

it('asks to select an anime before playing', function () {
    playAs(fake()->uuid())
        ->test('pages::animes')
        ->call('play')
        ->assertHasErrors('selected')
        ->assertNoRedirect();
});
