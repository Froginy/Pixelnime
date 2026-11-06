<?php

namespace App\Enums;

enum GameStatus: string
{
    case Playing = 'playing';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * Determine if the game is over.
     */
    public function isFinished(): bool
    {
        return $this !== self::Playing;
    }
}
