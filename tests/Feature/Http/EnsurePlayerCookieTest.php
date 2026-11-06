<?php

use App\Http\Middleware\EnsurePlayerCookie;

it('gives a new visitor an anonymous player cookie', function () {
    $this->get(route('home'))->assertCookie(EnsurePlayerCookie::COOKIE);
});

it('keeps the existing player cookie', function () {
    $this->withCookie(EnsurePlayerCookie::COOKIE, fake()->uuid())
        ->get(route('home'))
        ->assertCookieMissing(EnsurePlayerCookie::COOKIE);
});
