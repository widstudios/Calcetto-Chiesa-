<?php

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Dati di prova: un amministratore, un allenatore e un torneo con 4 squadre.
     * La password di entrambi gli utenti è "password".
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Amministratore',
            'email' => 'admin@calcetto.test',
        ]);

        $coach = User::factory()->create([
            'name' => 'Mario Rossi',
            'email' => 'allenatore@calcetto.test',
        ]);

        $tournament = Tournament::factory()->create([
            'name' => 'Torneo della Parrocchia',
        ]);

        $teams = collect(['Oratorio United', 'San Giuseppe', 'Real Campetto', 'Atletico Sagrato'])
            ->map(fn (string $name, int $i) => Team::factory()
                ->for($tournament)
                ->when($i === 0, fn ($factory) => $factory->withCoachAccount($coach))
                ->create(['name' => $name]));

        $teams->each(function (Team $team) {
            Player::factory()->count(5)->for($team)->sequence(fn ($s) => ['shirt_number' => $s->index + 1])->create();
            Player::factory()->count(3)->bench()->for($team)->sequence(fn ($s) => ['shirt_number' => $s->index + 12])->create();
        });

        // Girone all'italiana: ogni squadra incontra le altre una volta.
        $matchday = 1;
        $kickoff = $tournament->starts_on->copy()->setTime(20, 30);
        foreach ($teams as $i => $home) {
            foreach ($teams->slice($i + 1) as $away) {
                Game::factory()->for($tournament)->create([
                    'home_team_id' => $home->id,
                    'away_team_id' => $away->id,
                    'matchday' => $matchday++,
                    'kickoff_at' => $kickoff->addWeek()->copy(),
                ]);
            }
        }

        // Le prime tre giornate già giocate, con marcatori, voti e migliore in campo.
        $tournament->games()->with(['homeTeam.players', 'awayTeam.players'])->orderBy('matchday')->limit(3)->get()
            ->each(function (Game $game) {
                $scores = [];
                foreach (['homeTeam', 'awayTeam'] as $side) {
                    $players = $game->{$side}->players;
                    $scores[$side] = fake()->numberBetween(0, 5);
                    $scorers = collect(range(1, $scores[$side]))->map(fn () => $players->random()->id)->countBy();
                    foreach ($players as $player) {
                        $game->performances()->create([
                            'player_id' => $player->id,
                            'goals' => $scorers->get($player->id, 0),
                            'rating' => fake()->randomElement([5, 5.5, 6, 6, 6.5, 6.5, 7, 7.5, 8]),
                        ]);
                    }
                }

                $game->update([
                    'status' => GameStatus::Giocata,
                    'home_score' => $scores['homeTeam'],
                    'away_score' => $scores['awayTeam'],
                    'mvp_player_id' => $game->performances()->orderByDesc('rating')->orderByDesc('goals')->value('player_id'),
                ]);
            });
    }
}
