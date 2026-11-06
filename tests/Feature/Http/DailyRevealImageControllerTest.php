<?php

use App\Models\DailyChallenge;
use Illuminate\Support\Facades\Storage;

it('reveals the image of a past daily character', function () {
    Storage::fake('local');
    $challenge = DailyChallenge::factory()->create(['date' => today()->subDay()]);
    storeCharacterImage($challenge->character);

    $this->get(route('daily.image', $challenge))->assertOk();
});

it('returns 404 for today\'s daily character', function () {
    $challenge = DailyChallenge::factory()->create(['date' => today()]);

    $this->get(route('daily.image', $challenge))->assertNotFound();
});
