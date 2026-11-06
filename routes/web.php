<?php

use App\Http\Controllers\DailyRevealImageController;
use App\Http\Controllers\GameImageController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/daily', 'pages::daily')->name('daily');
Route::livewire('/infini', 'pages::infinite')->name('infinite');
Route::livewire('/animes', 'pages::animes')->name('animes');

Route::get('/parties/{game}/image', GameImageController::class)->name('games.image');
Route::get('/daily/{dailyChallenge}/image', DailyRevealImageController::class)->name('daily.image');
