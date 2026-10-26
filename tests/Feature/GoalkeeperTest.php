<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\TournamentStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalkeeperTest extends TestCase
{
    use RefreshDatabase;

    public function test_goalkeepers_are_chosen_in_the_sheet_and_ranked_by_goals_conceded(): void
    {
        $admin = User::factory()->admin()->create();
        $tournament = Tournament::factory()->create();
        [$alfa, $beta, $gamma] = Team::factory()->count(3)->for($tournament)->sequence(['name' => 'Alfa'], ['name' => 'Beta'], ['name' => 'Gamma'])->create();
        $keeperA = Player::factory()->for($alfa)->create(['first_name' => 'Mario', 'last_name' => 'Parata']);
        $keeperB = Player::factory()->for($beta)->create(['first_name' => 'Luigi', 'last_name' => 'Tuffo']);
        $keeperC = Player::factory()->for($gamma)->create(['first_name' => 'Nino', 'last_name' => 'Presa']);

        $first = Game::factory()->for($tournament)->played(2, 1)->create(['home_team_id' => $alfa->id, 'away_team_id' => $beta->id]);
        $second = Game::factory()->for($tournament)->played(0, 0)->create(['home_team_id' => $gamma->id, 'away_team_id' => $alfa->id]);

        $this->actingAs($admin)->get(route('games.sheet.edit', $first))->assertOk()->assertSee('Portiere');

        // Il portiere deve essere della sua squadra.
        $this->put(route('games.sheet.update', $first), ['goalkeepers' => [$alfa->id => $keeperB->id]])
            ->assertSessionHasErrors('goalkeepers.'.$alfa->id);

        $this->put(route('games.sheet.update', $first), ['goalkeepers' => [$alfa->id => $keeperA->id, $beta->id => $keeperB->id]])
            ->assertSessionHasNoErrors();
        $this->put(route('games.sheet.update', $second), ['goalkeepers' => [$gamma->id => $keeperC->id, $alfa->id => $keeperA->id]])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('performances', ['game_id' => $first->id, 'player_id' => $keeperA->id, 'goalkeeper' => true, 'goals' => 0]);

        $keepers = (new TournamentStats($tournament))->bestGoalkeepers();

        // Presa 0 subiti in 1 partita, Parata 1 in 2 (0,5 a partita), Tuffo 2 in 1.
        $this->assertSame(['Presa', 'Parata', 'Tuffo'], $keepers->pluck('last_name')->all());
        $this->assertSame([0, 1, 2], $keepers->pluck('goals_conceded')->all());
        $this->assertSame([1, 1, 0], $keepers->pluck('clean_sheets')->all());

        $this->get('/?torneo='.$tournament->id)->assertOk()->assertSee('Miglior portiere')->assertSee('Mario Parata');
        $this->get(route('standings.pdf', $tournament))->assertOk();

        // Tolto il portiere, la riga senza gol né voto sparisce.
        $this->put(route('games.sheet.update', $second), ['goalkeepers' => [$gamma->id => '', $alfa->id => $keeperA->id]]);
        $this->assertDatabaseMissing('performances', ['game_id' => $second->id, 'player_id' => $keeperC->id]);
    }
}
