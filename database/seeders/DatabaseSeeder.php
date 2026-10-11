<?php

namespace Database\Seeders;

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
    }
}
