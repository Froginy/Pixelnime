<?php

use App\Models\DailyChallenge;

it('shows yesterday\'s daily character', function () {
    $challenge = DailyChallenge::factory()->create(['date' => today()->subDay()]);

    playAs(fake()->uuid())
        ->test('pages::home')
        ->assertSee($challenge->character->name)
        ->assertSee(route('daily.image', $challenge));
});

it('does not reveal today\'s character', function () {
    $challenge = DailyChallenge::factory()->create(['date' => today()]);

    playAs(fake()->uuid())
        ->test('pages::home')
        ->assertDontSee($challenge->character->name)
        ->assertSee('Pas encore de personnage d');
});

it('offers the infinite mode difficulties', function () {
    playAs(fake()->uuid())
        ->test('pages::home')
        ->set('choosingDifficulty', true)
        ->assertSee(['Facile', 'Moyen', 'Difficile'])
        ->assertSee(route('infinite', ['difficulte' => 'hard']), false);
});

it('goes back to the menu with the window close button', function () {
    playAs(fake()->uuid())
        ->test('pages::home')
        ->assertDontSeeHtml('aria-label="Retour"')
        ->set('choosingDifficulty', true)
        ->assertSeeHtml('aria-label="Retour"')
        ->call('$set', 'choosingDifficulty', false)
        ->assertSee('Choisis ton mode de jeu');
});
