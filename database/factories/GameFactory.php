<?php

namespace Database\Factories;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
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
            'tournament_id' => Tournament::factory(),
            'home_team_id' => fn (array $attributes) => Team::factory()->create(['tournament_id' => $attributes['tournament_id']]),
            'away_team_id' => fn (array $attributes) => Team::factory()->create(['tournament_id' => $attributes['tournament_id']]),
            'matchday' => 1,
            'kickoff_at' => fake()->dateTimeBetween('now', '+1 month'),
            'venue' => 'Campo parrocchiale',
            'status' => GameStatus::Programmata,
        ];
    }

    public function played(int $homeScore, int $awayScore): static
    {
        return $this->state(fn (array $attributes) => [
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'status' => GameStatus::Giocata,
        ]);
    }
}
