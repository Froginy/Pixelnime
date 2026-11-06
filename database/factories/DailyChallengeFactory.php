<?php

namespace Database\Factories;

use App\Models\Character;
use App\Models\DailyChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyChallenge>
 */
class DailyChallengeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => today(),
            'character_id' => Character::factory(),
        ];
    }
}
