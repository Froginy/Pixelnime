<?php

use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Game;
use Illuminate\Support\Facades\Storage;

it('forbids a player from loading the image of someone else\'s game', function () {
    $game = Game::factory()->create();

    $this->withCookie(EnsurePlayerCookie::COOKIE, fake()->uuid())
        ->get(route('games.image', $game))
        ->assertForbidden();
});

it('serves the pixelated image while the game is in progress, even when a better level is requested', function () {
    Storage::fake('local');
    $game = Game::factory()->create();
    storeCharacterImage($game->character);

    $this->withCookie(EnsurePlayerCookie::COOKIE, $game->player_uuid)
        ->get(route('games.image', ['game' => $game, 'v' => Game::FULL_QUALITY_LEVEL]))
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');

    Storage::disk('local')->assertExists("pixelated/{$game->character_id}/1.png");
});

it('serves the original image once the game is over', function () {
    Storage::fake('local');
    $game = Game::factory()->won()->create();
    storeCharacterImage($game->character);

    $response = $this->withCookie(EnsurePlayerCookie::COOKIE, $game->player_uuid)
        ->get(route('games.image', ['game' => $game, 'v' => Game::FULL_QUALITY_LEVEL]))
        ->assertOk();

    expect($response->streamedContent())->toBe(Storage::disk('local')->get($game->character->image_path));
    Storage::disk('local')->assertMissing("pixelated/{$game->character_id}");
});
