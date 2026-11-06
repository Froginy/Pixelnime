<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Game;
use App\Services\ImagePixelator;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GameImageController extends Controller
{
    /**
     * Serve the character image at the quality level the player has unlocked, never better.
     */
    public function __invoke(Request $request, Game $game, ImagePixelator $pixelator): StreamedResponse
    {
        abort_unless($game->belongsToPlayer(EnsurePlayerCookie::playerUuid($request)), 403);

        $level = $game->revealLevel();
        $path = $pixelator->pathFor($game->character, $level);

        // The URL carries the level (?v=) so each level can be cached, except when it does not match.
        $cacheControl = $request->integer('v') === $level ? 'private, max-age=31536000' : 'no-store';

        return $pixelator->disk()->response($path, headers: ['Cache-Control' => $cacheControl]);
    }
}
