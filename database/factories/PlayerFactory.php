<?php

namespace Database\Factories;

use App\Enums\SquadRole;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'first_name' => fake()->firstNameMale(),
            'last_name' => fake()->lastName(),
            'birth_year' => fake()->numberBetween(1980, 2010),
            'shirt_number' => fake()->numberBetween(1, 99),
            'squad_role' => SquadRole::Titolare,
        ];
    }

    public function bench(): static
    {
        return $this->state(fn (array $attributes) => [
            'squad_role' => SquadRole::Panchina,
        ]);
    }
}
