<?php

use App\Http\Middleware\EnsurePlayerCookie;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\LivewireManager;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Store a small generated PNG as the character's image on the faked local disk.
 */
function storeCharacterImage(Character $character, int $width = 60, int $height = 90): void
{
    $image = imagecreatetruecolor($width, $height);

    for ($x = 0; $x < $width; $x++) {
        imagefilledrectangle($image, $x, 0, $x, $height - 1, imagecolorallocate($image, ($x * 37) % 256, ($x * 91) % 256, ($x * 13) % 256));
    }

    ob_start();
    imagepng($image);

    Storage::disk('local')->put($character->image_path, (string) ob_get_clean());
}

/**
 * Prepare Livewire tests sent by the given anonymous player.
 */
function playAs(string $playerUuid): LivewireManager
{
    return Livewire\Livewire::withCookie(EnsurePlayerCookie::COOKIE, $playerUuid);
}
