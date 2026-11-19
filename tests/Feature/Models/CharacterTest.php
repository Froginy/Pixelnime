<?php

use App\Models\Anime;
use App\Models\Character;

it('finds a character whatever the order of the words and the punctuation', function (string $term) {
    $luffy = Character::factory()->named('Luffy Monkey', ['Monkey D. Luffy', 'Mugiwara'])->create();
    Character::factory()->named('Zoro Roronoa')->create();

    expect(Character::query()->matching($term)->pluck('id')->all())->toBe([$luffy->id]);
})->with(['luffy monkey', 'Monkey D. Luffy', 'monkey-d-luffy', 'MUGIWARA']);

it('does not match when one of the words is missing', function () {
    Character::factory()->named('Luffy Monkey')->create();

    expect(Character::query()->matching('luffy zoro')->exists())->toBeFalse();
});

it('finds the characters of an anime from its title', function () {
    $anime = Anime::factory()->create(['title' => "JoJo's Bizarre Adventure: Golden Wind"]);
    $giorno = Character::factory()->for($anime)->named('Giorno Giovanna')->create();
    Character::factory()->named('Levi')->create();

    expect(Character::query()->fromAnimeMatching('golden wind')->pluck('id')->all())->toBe([$giorno->id]);
});

it('explains a suggestion with the alias that matched', function () {
    $luffy = Character::factory()->named('Luffy Monkey', ['Monkey D. Luffy', 'Mugiwara'])->make();

    expect($luffy->matchingAlias('mugiwara'))->toBe('Mugiwara')
        ->and($luffy->matchingAlias('luffy'))->toBeNull();
});

it('gives the initials of the character as a hint', function () {
    expect(Character::factory()->named('Giorno Giovanna')->make()->initials())->toBe('G. G.');
});
