<?php

use App\Models\Character;
use App\Models\Game;
use App\Services\ImagePixelator;
use Illuminate\Support\Facades\Storage;

it('keeps the original dimensions and caches each pixelated level', function () {
    Storage::fake('local');
    $character = Character::factory()->create();
    storeCharacterImage($character, 60, 90);

    $path = app(ImagePixelator::class)->pathFor($character, 1);

    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($path));

    Storage::disk('local')->assertExists("pixelated/{$character->id}/1.png");
    expect($path)->toBe("pixelated/{$character->id}/1.png")
        ->and([$width, $height])->toBe([60, 90]);
});

it('reduces the number of distinct colors at low levels', function () {
    Storage::fake('local');
    $character = Character::factory()->create();
    storeCharacterImage($character, 60, 90);

    $pixelator = app(ImagePixelator::class);
    $countColors = function (string $path): int {
        $image = imagecreatefromstring(Storage::disk('local')->get($path));
        $colors = [];
        for ($x = 0; $x < imagesx($image); $x++) {
            $colors[imagecolorat($image, $x, 0)] = true;
        }

        return count($colors);
    };

    expect($countColors($pixelator->pathFor($character, 1)))->toBeLessThanOrEqual(ImagePixelator::COLUMNS_PER_LEVEL[1])
        ->and($countColors($pixelator->pathFor($character, 5)))->toBeGreaterThan($countColors($pixelator->pathFor($character, 1)));
});

it('returns the original image at full quality', function () {
    $character = Character::factory()->create();

    expect(app(ImagePixelator::class)->pathFor($character, Game::FULL_QUALITY_LEVEL))->toBe($character->image_path);
});
