<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Anime;
use App\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->name();

        return [
            'anilist_id' => fake()->unique()->numberBetween(1, 9_999_999),
            'anime_id' => Anime::factory(),
            'name' => $name,
            'search_name' => Character::searchableText($name, []),
            'alternative_names' => [],
            'image_path' => 'characters/'.fake()->uuid().'.jpg',
            'favourites' => fake()->numberBetween(100, 100_000),
            'difficulty' => Difficulty::Easy,
        ];
    }

    /**
     * @param  list<string>  $alternativeNames
     */
    public function named(string $name, array $alternativeNames = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'alternative_names' => $alternativeNames,
            'search_name' => Character::searchableText($name, $alternativeNames),
        ]);
    }

    public function difficulty(Difficulty $difficulty): static
    {
        return $this->state(fn (array $attributes): array => ['difficulty' => $difficulty]);
    }
}
