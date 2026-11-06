<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Anime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anime>
 */
class AnimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anilist_id' => fake()->unique()->numberBetween(1, 9_999_999),
            'title' => fake()->unique()->words(3, true),
            'cover_path' => null,
            'popularity' => fake()->numberBetween(1_000, 500_000),
            'difficulty' => Difficulty::Easy,
        ];
    }

    public function difficulty(Difficulty $difficulty): static
    {
        return $this->state(fn (array $attributes): array => ['difficulty' => $difficulty]);
    }
}
