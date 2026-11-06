<?php

namespace App\Models;

use App\Enums\GameMode;
use App\Enums\GameStatus;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $player_uuid
 * @property GameMode $mode
 * @property int $character_id
 * @property int|null $daily_challenge_id
 * @property list<int> $guesses
 * @property GameStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Character $character
 * @property-read DailyChallenge|null $dailyChallenge
 */
#[Fillable(['player_uuid', 'mode', 'character_id', 'daily_challenge_id', 'guesses', 'status'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    public const int MAX_ATTEMPTS = 5;

    public const int FULL_QUALITY_LEVEL = self::MAX_ATTEMPTS + 1;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'guesses' => '[]',
        'status' => 'playing',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => GameMode::class,
            'guesses' => 'array',
            'status' => GameStatus::class,
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
     * @return BelongsTo<DailyChallenge, $this>
     */
    public function dailyChallenge(): BelongsTo
    {
        return $this->belongsTo(DailyChallenge::class);
    }

    /**
     * Get the image quality level the player is allowed to see (1 = most pixelated).
     */
    public function revealLevel(): int
    {
        if ($this->status->isFinished()) {
            return self::FULL_QUALITY_LEVEL;
        }

        return count($this->guesses) + 1;
    }

    public function remainingAttempts(): int
    {
        return self::MAX_ATTEMPTS - count($this->guesses);
    }

    public function belongsToPlayer(?string $playerUuid): bool
    {
        return $playerUuid !== null && hash_equals($this->player_uuid, $playerUuid);
    }

    public function hasGuessed(Character $character): bool
    {
        return in_array($character->id, $this->guesses, true);
    }

    public function canGuess(Character $character): bool
    {
        return ! $this->status->isFinished() && ! $this->hasGuessed($character);
    }

    /**
     * Record a guess and update the game status accordingly.
     */
    public function submitGuess(Character $character): void
    {
        if (! $this->canGuess($character)) {
            throw new LogicException('This guess is not allowed.');
        }

        $this->guesses = [...$this->guesses, $character->id];

        $this->status = match (true) {
            $character->id === $this->character_id => GameStatus::Won,
            count($this->guesses) >= self::MAX_ATTEMPTS => GameStatus::Lost,
            default => GameStatus::Playing,
        };

        $this->save();
    }

    /**
     * Get the guessed characters in the order they were submitted.
     *
     * @return Collection<int, Character>
     */
    public function guessedCharacters(): Collection
    {
        $characters = Character::query()->with('anime')->findMany($this->guesses)->keyBy('id');

        return new Collection(
            collect($this->guesses)->map(fn (int $id): ?Character => $characters->get($id))->filter()->values()->all()
        );
    }
}
