<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => fake()->unique()->lastName().' FC',
            'coach_name' => fake()->name(),
            'coach_id' => null,
        ];
    }

    /**
     * Squadra gestita da un allenatore con account.
     */
    public function withCoachAccount(?User $coach = null): static
    {
        return $this->state(fn (array $attributes) => [
            'coach_id' => $coach ?? User::factory(),
            'coach_name' => null,
        ]);
    }
}
