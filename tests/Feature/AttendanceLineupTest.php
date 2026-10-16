<?php

namespace Tests\Feature;

use App\Enums\SquadRole;
use App\Models\Attendance;
use App\Models\Game;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceLineupTest extends TestCase
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
        // L'allenamento inserito compare una volta sola, con l'ultimo mercoledì da segnare.
        $this->get(route('teams.attendances.show', $this->team))->assertOk()
            ->assertSee('Mercoledì · 18:00 - 19:30')->assertSee('14/10/2026')->assertSee('Appello da fare')
            ->assertDontSee('07/10/2026');
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
            'data' => '2026-10-07',
            'players' => [$marco->id => '1', $paolo->id => '0'],
        ]);

        $this->get(route('teams.attendances.show', $this->team))
            ->assertSee('2 presenti su 2')
            ->assertSee('1 presenti su 2')
            ->assertSee('50%');

        $this->delete(route('teams.attendances.destroy', $this->team), ['data' => '2026-10-07'])->assertRedirect();
        $this->assertSame(2, Attendance::count());
    }

    public function test_roll_call_only_on_scheduled_training_days(): void
    {
        $this->team->trainings()->create(['weekday' => 3, 'field' => 'Oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $player = Player::factory()->for($this->team)->create();

        // Giovedì 15: nessun allenamento in calendario, la data non si può usare.
        $this->actingAs($this->coach)->get(route('teams.attendances.edit', [$this->team, 'data' => '2026-10-15']))->assertSessionHasErrors('data');
        $this->put(route('teams.attendances.update', $this->team), ['data' => '2026-10-15', 'players' => [$player->id => '1']])->assertSessionHasErrors('data');
        $this->assertSame(0, Attendance::count());

        // Due orari nello stesso giorno: un solo appello per quella data.
        $this->team->trainings()->create(['weekday' => 3, 'field' => 'Palestra', 'starts_at' => '20:00', 'ends_at' => '21:00']);
        $this->get(route('teams.attendances.show', $this->team))->assertSee('Mercoledì · 18:00 - 19:30, 20:00 - 21:00')->assertSee('Oratorio, Palestra');

        // Nessun altro giorno in elenco: solo l'allenamento inserito.
        $this->get(route('teams.attendances.show', $this->team))->assertSee('14/10/2026')->assertSee('Mercoledì · 18:00 - 19:30')->assertDontSee('Giovedì');
    }

    public function test_roll_call_rejects_future_dates_and_other_teams(): void
    {
        $this->team->trainings()->create(['weekday' => 5, 'field' => 'Oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $player = Player::factory()->for($this->team)->create();
        $other = Player::factory()->create();

        $this->actingAs($this->coach)
            ->put(route('teams.attendances.update', $this->team), ['data' => '2026-10-23', 'players' => [$player->id => '1']])
            ->assertSessionHasErrors('data');

        $this->put(route('teams.attendances.update', $this->team), ['data' => '2026-10-16', 'players' => [$player->id => '1', $other->id => '0']]);
        $this->assertSame([$player->id], Attendance::pluck('player_id')->all());

        $this->get(route('teams.attendances.show', $other->team))->assertForbidden();
        $this->put(route('teams.attendances.update', $other->team), ['data' => '2026-10-14', 'players' => [$other->id => '1']])->assertForbidden();
    }

    public function test_coach_makes_lineup_without_a_game(): void
    {
        $starter = Player::factory()->for($this->team)->create(['first_name' => 'Marco', 'last_name' => 'Verdi', 'shirt_number' => 10, 'squad_role' => SquadRole::Titolare]);
        $bench = Player::factory()->for($this->team)->create(['first_name' => 'Paolo', 'last_name' => 'Neri', 'shirt_number' => 12, 'squad_role' => SquadRole::Panchina]);
        $left = Player::factory()->for($this->team)->create(['last_name' => 'Escluso', 'squad_role' => SquadRole::Panchina]);

        $this->actingAs($this->coach)->get(route('lineups.index'))->assertRedirect(route('teams.lineups.index', $this->team));
        $this->get(route('teams.lineups.index', $this->team))->assertOk()->assertSee('Nessuna distinta');
        $this->get(route('teams.lineups.create', $this->team))->assertOk()->assertSee('Marco Verdi');

        $this->post(route('teams.lineups.store', $this->team), [
            'kickoff_at' => '2026-10-18T10:00',
            'opponent' => 'Amici del Lunedì',
            'players' => [
                $starter->id => ['called' => '1', 'shirt_number' => 10],
                $bench->id => ['called' => '1', 'shirt_number' => 7],
                $left->id => ['shirt_number' => 5],
            ],
            'captain' => $starter->id,
            'vice' => $bench->id,
        ])->assertRedirect();

        $lineup = Lineup::sole();
        $this->assertNull($lineup->game_id);
        $this->assertSame(2, $lineup->players()->count());
        $this->assertSame(LineupPlayer::VICE, $lineup->players()->firstWhere('player_id', $bench->id)->captain);
        $this->get(route('teams.lineups.print', [$this->team, $lineup]))->assertOk()
            ->assertSee($this->team->name.' – Amici del Lunedì')
            ->assertSeeInOrder(['Neri Paolo', 'Verdi Marco'])
            ->assertDontSee('Escluso')
            ->assertSee('18/10/2026 10:00');

        // Modifica: Paolo resta fuori.
        $this->put(route('teams.lineups.update', [$this->team, $lineup]), [
            'players' => [$starter->id => ['called' => '1', 'shirt_number' => 10]],
        ])->assertRedirect(route('teams.lineups.print', [$this->team, $lineup]));
        $this->assertSame([$starter->id], $lineup->players()->pluck('player_id')->all());

        $this->delete(route('teams.lineups.destroy', [$this->team, $lineup]))->assertRedirect(route('teams.lineups.index', $this->team));
        $this->assertSame(0, Lineup::count());
    }

    public function test_lineup_takes_details_from_the_chosen_game(): void
    {
        $game = Game::factory()->create(['home_team_id' => $this->team->id, 'tournament_id' => $this->team->tournament_id, 'kickoff_at' => '2026-10-25 09:30', 'venue' => 'Campo parrocchiale']);
        $player = Player::factory()->for($this->team)->create();
        $foreign = Game::factory()->create();

        $this->actingAs($this->coach)->post(route('teams.lineups.store', $this->team), [
            'game_id' => $foreign->id,
            'players' => [$player->id => ['called' => '1']],
        ])->assertSessionHasErrors('game_id');

        $this->post(route('teams.lineups.store', $this->team), [
            'game_id' => $game->id,
            'players' => [$player->id => ['called' => '1']],
        ])->assertRedirect();

        $lineup = Lineup::sole();
        $this->assertSame($game->awayTeam->name, $lineup->opponent);
        $this->assertSame('Campo parrocchiale', $lineup->venue);
        $this->get(route('teams.lineups.print', [$this->team, $lineup]))->assertSee('25/10/2026 09:30')->assertSee('1ª giornata');
    }

    public function test_lineup_validation(): void
    {
        [$a, $b, $c] = Player::factory()->for($this->team)->count(3)->create();

        $this->actingAs($this->coach)->post(route('teams.lineups.store', $this->team), [
            'players' => [$a->id => ['called' => '1', 'shirt_number' => 9], $b->id => ['called' => '1', 'shirt_number' => 9]],
            'captain' => $c->id,
        ])->assertSessionHasErrors(['players', 'captain']);

        $this->post(route('teams.lineups.store', $this->team), ['players' => []])->assertSessionHasErrors('players');
        $this->assertSame(0, Lineup::count());
    }

    public function test_lineups_only_for_own_teams(): void
    {
        $other = Team::factory()->create();
        $lineup = $other->lineups()->create(['opponent' => 'X']);

        $this->actingAs($this->coach)->get(route('teams.lineups.index', $other))->assertForbidden();
        $this->get(route('teams.lineups.print', [$other, $lineup]))->assertForbidden();
        // La distinta di un'altra squadra non si apre passando dalla propria.
        $this->get(route('teams.lineups.edit', [$this->team, $lineup]))->assertNotFound();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('lineups.index'))->assertOk()->assertSee($this->team->name)->assertSee($other->name);
        $this->get(route('teams.lineups.print', [$other, $lineup]))->assertOk();
        $this->get(route('attendances.index'))->assertOk()->assertSee($this->team->name);
    }
}
