<?php

namespace App\Services;

use App\Models\Character;
use App\Models\Game;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImagePixelator
{
    /**
     * Number of pixel columns kept for each reveal level (the full image is shown after the last one).
     *
     * @var array<int, int>
     */
    public const array COLUMNS_PER_LEVEL = [1 => 8, 2 => 12, 3 => 20, 4 => 32, 5 => 56];

    /**
     * Get the storage path of the character image at the given reveal level, generating it if needed.
     */
    public function pathFor(Character $character, int $level): string
    {
        if ($level >= Game::FULL_QUALITY_LEVEL) {
            return $character->image_path;
        }

        $level = max(1, $level);
        $path = "pixelated/{$character->id}/{$level}.png";

        if (! $this->disk()->exists($path)) {
            $original = $this->disk()->get($character->image_path)
                ?? throw new RuntimeException("Missing image for character {$character->id}.");

            $this->disk()->put($path, $this->pixelate($original, self::COLUMNS_PER_LEVEL[$level]));
        }

        return $path;
    }

    /**
     * Pixelate an image while keeping its original dimensions.
     */
    public function pixelate(string $imageData, int $columns): string
    {
        $source = imagecreatefromstring($imageData)
            ?: throw new RuntimeException('Unreadable image.');

        $width = imagesx($source);
        $height = imagesy($source);
        $smallWidth = max(1, min($columns, $width));
        $smallHeight = max(1, (int) round($height * $smallWidth / $width));

        $small = imagecreatetruecolor($smallWidth, $smallHeight);
        imagecopyresampled($small, $source, 0, 0, 0, 0, $smallWidth, $smallHeight, $width, $height);

        $pixelated = imagescale($small, $width, $height, IMG_NEAREST_NEIGHBOUR)
            ?: throw new RuntimeException('Unable to scale image.');

        ob_start();
        imagepng($pixelated);

        return (string) ob_get_clean();
    }

    public function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }
}
