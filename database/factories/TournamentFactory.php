<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+2 months');

        return [
            'name' => 'Torneo '.fake()->city(),
            'season' => $start->format('Y').'/'.((int) $start->format('Y') + 1),
            'starts_on' => $start,
            'ends_on' => (clone $start)->modify('+3 months'),
            'description' => fake()->sentence(),
        ];
    }
}
