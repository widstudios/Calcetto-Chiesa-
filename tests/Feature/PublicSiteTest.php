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

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_works_without_tournaments(): void
    {
        $this->get('/')->assertOk()->assertSee('Il torneo sta per cominciare');
    }

    public function test_home_shows_game_rules(): void
    {
        $this->get('/')->assertOk()->assertSee('Regole di gioco')->assertSee('falli cumulativi')->assertSee('Oratorio Cup')->assertSee('3 tempi da 15 minuti');
    }

    public function test_standings_follow_points_and_goal_difference(): void
    {
        $tournament = Tournament::factory()->create();
        [$a, $b, $c] = Team::factory()->count(3)->for($tournament)->sequence(['name' => 'Alfa'], ['name' => 'Beta'], ['name' => 'Gamma'])->create();

        Game::factory()->for($tournament)->played(3, 0)->create(['home_team_id' => $a->id, 'away_team_id' => $b->id]);
        Game::factory()->for($tournament)->played(1, 1)->create(['home_team_id' => $b->id, 'away_team_id' => $c->id]);
        Game::factory()->for($tournament)->create(['home_team_id' => $c->id, 'away_team_id' => $a->id]);

        $standings = (new TournamentStats($tournament))->standings();

        $this->assertSame(['Alfa', 'Gamma', 'Beta'], $standings->pluck('team.name')->all());
        $this->assertSame([3, 1, 1], $standings->pluck('points')->all());
        $this->assertSame(['V'], $standings->first()->form);

        $this->get('/')->assertOk()->assertSeeInOrder(['Classifica', 'Alfa', 'Gamma', 'Beta']);
    }

    public function test_admin_records_scorers_ratings_and_mvp(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->played(2, 1)->create();
        $striker = Player::factory()->for($game->homeTeam)->create(['first_name' => 'Gianni', 'last_name' => 'Gol']);
        $keeper = Player::factory()->for($game->awayTeam)->create();
        $outsider = Player::factory()->create();

        $this->actingAs($admin)->get(route('games.sheet.edit', $game))->assertOk()->assertSee('Gianni Gol');

        $this->actingAs($admin)->put(route('games.sheet.update', $game), [
            'players' => [
                $striker->id => ['goals' => 2, 'rating' => 8.5],
                $keeper->id => ['goals' => '', 'rating' => 6],
                $outsider->id => ['goals' => 5, 'rating' => 10],
            ],
            'mvp_player_id' => $striker->id,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(2, $game->performances()->where('player_id', $striker->id)->value('goals'));
        $this->assertDatabaseMissing('performances', ['player_id' => $outsider->id]);
        $this->assertTrue($game->fresh()->mvp->is($striker));

        $stats = new TournamentStats($game->tournament);
        $this->assertTrue($stats->topScorers()->first()->is($striker));
        $this->assertEquals(8.5, $stats->bestRatings()->first()->average_rating);
        $this->assertSame(1, $stats->mvpLeaders()->first()->mvp_count);

        $this->get('/?torneo='.$game->tournament_id)->assertOk()->assertSee('Migliore in campo: Gianni Gol');
    }

    public function test_sheet_rejects_mvp_from_other_teams_and_unplayed_games(): void
    {
        $admin = User::factory()->admin()->create();
        $game = Game::factory()->played(0, 0)->create();
        $outsider = Player::factory()->create();

        $this->actingAs($admin)->put(route('games.sheet.update', $game), ['mvp_player_id' => $outsider->id])
            ->assertSessionHasErrors('mvp_player_id');

        // Partita non ancora giocata: si va prima a inserire il risultato.
        $scheduled = Game::factory()->create();
        $this->actingAs($admin)->get(route('games.sheet.edit', $scheduled))
            ->assertRedirect(route('games.edit', $scheduled))->assertSessionHas('status');
    }

    public function test_coach_cannot_edit_sheet(): void
    {
        $game = Game::factory()->played(1, 0)->create();

        $this->actingAs(User::factory()->create())->get(route('games.sheet.edit', $game))->assertForbidden();
    }
}
