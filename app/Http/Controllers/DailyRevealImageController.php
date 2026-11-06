<?php

namespace App\Http\Controllers;

use App\Models\DailyChallenge;
use App\Services\ImagePixelator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyRevealImageController extends Controller
{
    /**
     * Serve the full image of a past daily character, once its day is over.
     */
    public function __invoke(DailyChallenge $dailyChallenge, ImagePixelator $pixelator): StreamedResponse
    {
        abort_unless($dailyChallenge->date->isBefore(today()), 404);

        return $pixelator->disk()->response($dailyChallenge->character->image_path, headers: [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
