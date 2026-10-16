<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Enums\SquadRole;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_pages_render(): void
    {
        $team = Team::factory()->create();
        $player = Player::factory()->for($team)->create();
        $game = Game::factory()->create();
        $coach = User::factory()->create();

        $this->actingAs($this->admin);

        foreach ([
            route('tournaments.index'), route('tournaments.create'), route('tournaments.edit', $team->tournament),
            route('teams.index'), route('teams.create'), route('teams.show', $team), route('teams.edit', $team),
            route('teams.players.create', $team), route('teams.players.edit', [$team, $player]),
            route('coaches.index'), route('coaches.create'), route('coaches.edit', $coach),
            route('games.index'), route('games.create', ['tournament' => $game->tournament_id]), route('games.edit', $game),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_creates_tournament_team_and_players(): void
    {
        $this->actingAs($this->admin)
            ->post(route('tournaments.store'), ['name' => 'Torneo Estivo', 'season' => '2026'])
            ->assertRedirect(route('tournaments.index'));
        $tournament = Tournament::firstWhere('name', 'Torneo Estivo');

        $this->post(route('teams.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'Oratorio United',
            'coach_name' => 'Luca Bianchi',
        ])->assertRedirect();
        $team = Team::firstWhere('name', 'Oratorio United');
        $this->assertSame('Luca Bianchi', $team->coachDisplayName());

        $this->post(route('teams.players.store', $team), [
            'first_name' => 'Marco', 'last_name' => 'Verdi', 'birth_year' => 2001,
            'phone' => '333 000 0003', 'shirt_number' => 10, 'squad_role' => 'titolare',
        ])->assertRedirect(route('teams.show', $team));
        $this->assertSame(['333 000 0003'], $team->players()->sole()->phones());

        $this->post(route('teams.players.store', $team), [
            'first_name' => 'Paolo', 'last_name' => 'Neri', 'birth_year' => 2004,
            'shirt_number' => 12, 'squad_role' => 'panchina', 'another' => 1,
        ])->assertRedirect(route('teams.players.create', $team));

        $this->assertCount(1, $team->starters);
        $this->assertCount(1, $team->bench);
        $this->get(route('teams.show', $team))->assertSee('Marco Verdi')->assertSee('2004');
    }

    public function test_player_validation(): void
    {
        $team = Team::factory()->create();
        Player::factory()->for($team)->create(['shirt_number' => 7]);

        $this->actingAs($this->admin)
            ->post(route('teams.players.store', $team), [
                'first_name' => 'A', 'last_name' => 'B', 'birth_year' => now()->year + 1,
                'shirt_number' => 7, 'squad_role' => 'riserva', 'phone' => 'non so', 'phone_2' => '------',
            ])
            ->assertSessionHasErrors(['birth_year', 'shirt_number', 'squad_role', 'phone', 'phone_2']);
    }

    public function test_player_route_is_scoped_to_its_team(): void
    {
        $player = Player::factory()->create();
        $otherTeam = Team::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('teams.players.edit', [$otherTeam, $player]))
            ->assertNotFound();
    }

    public function test_admin_creates_coach_account_that_can_log_in(): void
    {
        $team = Team::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('coaches.store'), [
                'name' => 'Mario Rossi', 'email' => 'mario@example.com',
                'password' => 'segreta123', 'teams' => [$team->id],
            ])
            ->assertRedirect(route('coaches.index'));

        $coach = User::firstWhere('email', 'mario@example.com');
        $this->assertSame(Role::Allenatore, $coach->role);
        $this->assertTrue($team->fresh()->coach->is($coach));

        $this->post(route('logout'));
        $this->post('/login', ['email' => 'mario@example.com', 'password' => 'segreta123']);
        $this->assertAuthenticatedAs($coach);
    }

    public function test_editing_coach_without_password_keeps_it(): void
    {
        $coach = User::factory()->create();
        $hash = $coach->password;

        $this->actingAs($this->admin)
            ->put(route('coaches.update', $coach), ['name' => 'Nuovo Nome', 'email' => $coach->email, 'password' => ''])
            ->assertRedirect(route('coaches.index'));

        $this->assertSame('Nuovo Nome', $coach->fresh()->name);
        $this->assertSame($hash, $coach->fresh()->password);
    }

    public function test_admin_schedules_game_and_records_result(): void
    {
        $tournament = Tournament::factory()->create();
        [$home, $away] = Team::factory()->count(2)->for($tournament)->create();
        $foreign = Team::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('games.store'), [
                'tournament_id' => $tournament->id, 'home_team_id' => $home->id, 'away_team_id' => $home->id,
                'status' => 'programmata',
            ])
            ->assertSessionHasErrors('away_team_id');

        $this->post(route('games.store'), [
            'tournament_id' => $tournament->id, 'home_team_id' => $home->id, 'away_team_id' => $foreign->id,
            'status' => 'programmata',
        ])->assertSessionHasErrors('away_team_id');

        $this->post(route('games.store'), [
            'tournament_id' => $tournament->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id,
            'matchday' => 1, 'kickoff_at' => '2026-11-01T20:30', 'status' => 'programmata', 'home_score' => 3,
        ])->assertRedirect(route('games.index', ['tournament' => $tournament->id]));

        $game = Game::sole();
        $this->assertNull($game->home_score);

        $this->put(route('games.update', $game), [
            'home_team_id' => $home->id, 'away_team_id' => $away->id, 'status' => 'giocata',
        ])->assertSessionHasErrors(['home_score', 'away_score']);

        $this->put(route('games.update', $game), [
            'home_team_id' => $home->id, 'away_team_id' => $away->id, 'status' => 'giocata',
            'home_score' => 4, 'away_score' => 2,
        ])->assertRedirect();

        $game->refresh();
        $this->assertSame(GameStatus::Giocata, $game->status);
        $this->assertSame([4, 2], [$game->home_score, $game->away_score]);
    }

    public function test_coach_manages_only_own_roster(): void
    {
        $coach = User::factory()->create();
        $mine = Team::factory()->withCoachAccount($coach)->create();
        $other = Team::factory()->create();
        $otherPlayer = Player::factory()->for($other)->create();

        $this->actingAs($coach);

        $this->get(route('teams.index'))->assertOk()->assertSee($mine->name)->assertDontSee($other->name);
        $this->get(route('teams.show', $mine))->assertOk();
        $this->post(route('teams.players.store', $mine), [
            'first_name' => 'Gianni', 'last_name' => 'Bruni', 'birth_year' => 1999, 'squad_role' => 'panchina',
        ])->assertRedirect(route('teams.show', $mine));
        $this->assertSame(SquadRole::Panchina, $mine->players()->sole()->squad_role);

        $this->get(route('teams.show', $other))->assertForbidden();
        $this->delete(route('teams.players.destroy', [$other, $otherPlayer]))->assertForbidden();
        $this->get(route('teams.edit', $mine))->assertForbidden();
        $this->get(route('teams.create'))->assertForbidden();
    }

    public function test_coach_cannot_open_admin_sections(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tournaments.index'))->assertForbidden();
        $this->get(route('coaches.index'))->assertForbidden();
        $this->get(route('games.index'))->assertForbidden();
    }
}
