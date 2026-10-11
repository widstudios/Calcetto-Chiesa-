<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_tournaments_and_upcoming_games(): void
    {
        $tournament = Tournament::factory()->create(['name' => 'Torneo di Prova']);
        Game::factory()->for($tournament)->create(['kickoff_at' => now()->addDay()]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Pannello amministratore')
            ->assertSee('Torneo di Prova');
    }

    public function test_coach_sees_only_own_roster(): void
    {
        $coach = User::factory()->create();
        $mine = Team::factory()->withCoachAccount($coach)->create(['name' => 'Oratorio United']);
        Player::factory()->for($mine)->create(['last_name' => 'Titolarissimo', 'birth_year' => 1995]);
        Player::factory()->bench()->for($mine)->create(['last_name' => 'Riserva']);
        $other = Team::factory()->create(['name' => 'Altra Squadra']);
        Player::factory()->for($other)->create(['last_name' => 'Estraneo']);

        $this->actingAs($coach)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Oratorio United')
            ->assertSee('Titolarissimo')
            ->assertSee('1995')
            ->assertSee('Panchina')
            ->assertDontSee('Altra Squadra')
            ->assertDontSee('Estraneo');
    }
}
