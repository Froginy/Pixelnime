<?php

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    /**
     * Get the French label shown to players.
     */
    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Facile',
            self::Medium => 'Moyen',
            self::Hard => 'Difficile',
        };
    }

    /**
     * Get the difficulty matching a popularity rank: the top 15% are easy, the next 35% medium, the rest hard.
     */
    public static function fromRank(int $rank, int $total): self
    {
        $ratio = $total > 0 ? $rank / $total : 0;

        return match (true) {
            $ratio < 0.15 => self::Easy,
            $ratio < 0.5 => self::Medium,
            default => self::Hard,
        };
    }
}
