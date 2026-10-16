<?php

namespace Tests\Feature;

use App\Enums\SquadRole;
use App\Models\Attendance;
use App\Models\Callup;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCallupTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-16 18:00'); // venerdì
        $this->coach = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->team->coaches()->attach($this->coach);
    }

    public function test_coach_takes_roll_call_and_sees_summary(): void
    {
        $this->team->trainings()->create(['weekday' => 3, 'field' => 'Oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $marco = Player::factory()->for($this->team)->create(['first_name' => 'Marco', 'last_name' => 'Verdi']);
        $paolo = Player::factory()->for($this->team)->create(['first_name' => 'Paolo', 'last_name' => 'Neri']);

        $this->actingAs($this->coach)->get(route('attendances.index'))->assertRedirect(route('teams.attendances.show', $this->team));
        // Il mercoledì di allenamento compare come appello da fare.
        $this->get(route('teams.attendances.show', $this->team))->assertOk()->assertSee('Mercoledì 14 ottobre 2026')->assertSee('Appello da fare');
        $this->get(route('teams.attendances.edit', [$this->team, 'data' => '2026-10-14']))->assertOk()->assertSee('Marco Verdi')->assertSee('18:00 - 19:30');

        $this->put(route('teams.attendances.update', $this->team), [
            'data' => '2026-10-14',
            'players' => [$marco->id => '1', $paolo->id => '0'],
        ])->assertRedirect(route('teams.attendances.show', $this->team));

        // Correggere lo stesso giorno aggiorna le righe, non le duplica.
        $this->put(route('teams.attendances.update', $this->team), [
            'data' => '2026-10-14',
            'players' => [$marco->id => '1', $paolo->id => '1'],
        ])->assertRedirect();
        $this->assertSame(2, Attendance::count());
        $this->put(route('teams.attendances.update', $this->team), [
            'data' => '2026-10-15',
            'players' => [$marco->id => '1', $paolo->id => '0'],
        ]);

        $this->get(route('teams.attendances.show', $this->team))
            ->assertSee('2 presenti su 2')
            ->assertSee('1 presenti su 2')
            ->assertSee('50%');

        $this->delete(route('teams.attendances.destroy', $this->team), ['data' => '2026-10-15'])->assertRedirect();
        $this->assertSame(2, Attendance::count());
    }

    public function test_roll_call_rejects_future_dates_and_other_teams(): void
    {
        $player = Player::factory()->for($this->team)->create();
        $other = Player::factory()->create();

        $this->actingAs($this->coach)
            ->put(route('teams.attendances.update', $this->team), ['data' => '2026-10-20', 'players' => [$player->id => '1']])
            ->assertSessionHasErrors('data');

        $this->put(route('teams.attendances.update', $this->team), ['data' => '2026-10-16', 'players' => [$player->id => '1', $other->id => '0']]);
        $this->assertSame([$player->id], Attendance::pluck('player_id')->all());

        $this->get(route('teams.attendances.show', $other->team))->assertForbidden();
        $this->put(route('teams.attendances.update', $other->team), ['data' => '2026-10-16', 'players' => [$other->id => '1']])->assertForbidden();
    }

    public function test_coach_fills_and_prints_callup(): void
    {
        $game = Game::factory()->create(['home_team_id' => $this->team->id, 'tournament_id' => $this->team->tournament_id, 'kickoff_at' => '2026-10-18 10:00']);
        $starter = Player::factory()->for($this->team)->create(['first_name' => 'Marco', 'last_name' => 'Verdi', 'shirt_number' => 10, 'squad_role' => SquadRole::Titolare]);
        $bench = Player::factory()->for($this->team)->create(['first_name' => 'Paolo', 'last_name' => 'Neri', 'shirt_number' => 12, 'squad_role' => SquadRole::Panchina]);
        $left = Player::factory()->for($this->team)->create(['last_name' => 'Escluso', 'squad_role' => SquadRole::Panchina]);

        $this->actingAs($this->coach)->get(route('callups.index'))->assertOk()->assertSee('Distinta '.$this->team->name)
            ->assertDontSee(route('games.callups.edit', [$game, $game->awayTeam]));
        $this->get(route('games.callups.edit', [$game, $this->team]))->assertOk()->assertSee('Marco Verdi');

        $this->put(route('games.callups.update', [$game, $this->team]), [
            'players' => [
                $starter->id => ['called' => '1', 'shirt_number' => 10],
                $bench->id => ['called' => '1', 'shirt_number' => 7],
                $left->id => ['shirt_number' => 5],
            ],
            'captain' => $starter->id,
            'vice' => $bench->id,
        ])->assertRedirect(route('games.callups.print', [$game, $this->team]));

        $this->assertSame(2, Callup::count());
        $this->assertSame(Callup::VICE, Callup::firstWhere('player_id', $bench->id)->captain);
        $this->get(route('games.callups.print', [$game, $this->team]))->assertOk()
            ->assertSeeInOrder(['Neri Paolo', 'Verdi Marco'])
            ->assertDontSee('Escluso')
            ->assertSee('18/10/2026 10:00');
    }

    public function test_callup_validation(): void
    {
        $game = Game::factory()->create(['home_team_id' => $this->team->id, 'tournament_id' => $this->team->tournament_id]);
        [$a, $b, $c] = Player::factory()->for($this->team)->count(3)->create();

        $this->actingAs($this->coach)->put(route('games.callups.update', [$game, $this->team]), [
            'players' => [$a->id => ['called' => '1', 'shirt_number' => 9], $b->id => ['called' => '1', 'shirt_number' => 9]],
            'captain' => $c->id,
        ])->assertSessionHasErrors(['players', 'captain']);

        $this->put(route('games.callups.update', [$game, $this->team]), ['players' => []])->assertSessionHasErrors('players');
        $this->assertSame(0, Callup::count());
    }

    public function test_callup_only_for_own_team_in_the_game(): void
    {
        $game = Game::factory()->create();
        $this->actingAs($this->coach)->get(route('games.callups.edit', [$game, $game->homeTeam]))->assertForbidden();
        $this->get(route('games.callups.edit', [$game, $this->team]))->assertNotFound();
        $this->get(route('callups.index'))->assertOk()->assertDontSee($game->homeTeam->name);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('games.callups.edit', [$game, $game->homeTeam]))->assertOk();
        $this->get(route('attendances.index'))->assertOk()->assertSee($this->team->name);
        $this->get(route('callups.index'))->assertOk()->assertSee('Distinta '.$game->awayTeam->name);
    }
}
