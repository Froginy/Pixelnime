<?php

namespace App\Models;

use App\Enums\Difficulty;
use Carbon\CarbonInterface;
use Database\Factories\DailyChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @property int $id
 * @property Carbon $date
 * @property int $character_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Character $character
 */
#[Fillable(['date', 'character_id'])]
class DailyChallenge extends Model
{
    /** @use HasFactory<DailyChallengeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Character, $this>
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Get the challenge of the given day, picking its character on first access.
     */
    public static function forDate(CarbonInterface $date): self
    {
        $day = $date->toDateString();

        $existing = static::query()->whereDate('date', $day)->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->firstOrCreate(
            ['date' => $day],
            ['character_id' => static::pickCharacterId($date)],
        );
    }

    /**
     * Get yesterday's challenge if one was played.
     */
    public static function yesterday(): ?self
    {
        return static::query()->whereDate('date', today()->subDay()->toDateString())->first();
    }

    /**
     * Pick the best known character possible that has not been used during the last year.
     */
    protected static function pickCharacterId(CarbonInterface $date): int
    {
        $recentlyUsed = static::query()
            ->whereDate('date', '>=', $date->copy()->subYear()->toDateString())
            ->pluck('character_id');

        $unused = fn (Difficulty $difficulty): ?int => Character::query()
            ->where('difficulty', $difficulty)
            ->whereNotIn('id', $recentlyUsed)
            ->inRandomOrder()
            ->value('id');

        $characterId = $unused(Difficulty::Easy)
            ?? $unused(Difficulty::Medium)
            ?? Character::query()->inRandomOrder()->value('id');

        if ($characterId === null) {
            throw new RuntimeException('Aucun personnage en base : lance `php artisan pixelnime:import`.');
        }

        return $characterId;
    }
}
