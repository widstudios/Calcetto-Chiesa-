<?php

namespace Tests\Feature;

use App\Enums\SquadRole;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_splits_roster_into_starters_and_bench(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(5)->for($team)->create();
        Player::factory()->count(2)->bench()->for($team)->create();

        $this->assertCount(7, $team->players);
        $this->assertCount(5, $team->starters);
        $this->assertCount(2, $team->bench);
        $this->assertSame(SquadRole::Panchina, $team->bench->first()->squad_role);
    }

    public function test_coach_name_falls_back_to_account_name(): void
    {
        $this->assertSame('Luca Bianchi', Team::factory()->create(['coach_name' => 'Luca Bianchi'])->coachDisplayName());

        $coach = User::factory()->create(['name' => 'Mario Rossi']);
        $this->assertSame('Mario Rossi', Team::factory()->withCoachAccount($coach)->create()->coachDisplayName());
    }

    public function test_team_games_include_home_and_away(): void
    {
        $game = Game::factory()->played(3, 2)->create();
        $home = $game->homeTeam;

        Game::factory()->for($game->tournament)->create([
            'home_team_id' => Team::factory()->for($game->tournament),
            'away_team_id' => $home->id,
        ]);

        $this->assertSame(2, $home->games()->count());
        $this->assertTrue($game->isPlayed());
        $this->assertCount(2, $game->tournament->games);
    }

    public function test_seeder_builds_a_demo_tournament(): void
    {
        $this->seed();

        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(4, Team::count());
        $this->assertSame(6, Game::count());
    }
}
