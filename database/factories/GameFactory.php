<?php

namespace Database\Factories;

use App\Enums\GameMode;
use App\Enums\GameStatus;
use App\Models\Character;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_uuid' => fake()->uuid(),
            'mode' => GameMode::Infinite,
            'character_id' => Character::factory(),
            'daily_challenge_id' => null,
            'guesses' => [],
            'status' => GameStatus::Playing,
        ];
    }

    public function forPlayer(string $playerUuid): static
    {
        return $this->state(fn (array $attributes): array => ['player_uuid' => $playerUuid]);
    }

    public function won(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => GameStatus::Won]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => GameStatus::Lost]);
    }
}
