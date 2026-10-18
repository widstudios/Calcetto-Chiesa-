<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-21 12:00'); // mercoledì
        $this->coach = User::factory()->create();
        $this->team = Team::factory()->create(['name' => 'Under 10 San Nicola']);
        $this->team->coaches()->attach($this->coach);
    }

    public function test_coach_sees_trainings_games_and_events_of_the_week(): void
    {
        $other = Team::factory()->create(['name' => 'Squadra Altrui']);
        $this->team->trainings()->create(['weekday' => 2, 'field' => 'Campo oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $other->trainings()->create(['weekday' => 2, 'field' => 'Campo lontano', 'starts_at' => '17:00', 'ends_at' => '18:00']);
        Game::factory()->create([
            'tournament_id' => $this->team->tournament_id, 'home_team_id' => $this->team->id, 'away_team_id' => $other->id,
            'kickoff_at' => '2026-10-25 15:30', 'venue' => 'Campo centrale',
        ]);
        CalendarEvent::create(['title' => 'Riunione allenatori', 'type' => 'riunione', 'date' => '2026-10-19', 'starts_at' => '09:30', 'ends_at' => '11:00', 'location' => 'Sala riunioni A']);
        CalendarEvent::create(['title' => 'Cena squadra altrui', 'type' => 'altro', 'team_id' => $other->id, 'date' => '2026-10-20', 'starts_at' => '20:00']);
        CalendarEvent::create(['title' => 'Riunione della prossima settimana', 'type' => 'riunione', 'date' => '2026-10-27', 'starts_at' => '09:30']);

        $this->actingAs($this->coach)->get(route('calendar.index'))->assertOk()
            ->assertSee('19 - 25 ottobre 2026')
            ->assertSee('Allenamento Under 10 San Nicola')->assertSee('Campo oratorio')->assertSee('18:00 - 19:30')
            ->assertSee('Campo centrale')->assertSee('15:30')
            ->assertSee('Riunione allenatori')->assertSee('Sala riunioni A')->assertSee('Tutta la società')
            ->assertDontSee('Campo lontano')->assertDontSee('Cena squadra altrui')->assertDontSee('Riunione della prossima settimana')
            // Allenamento di ieri: si apre l'appello di quel giorno.
            ->assertSee(route('teams.attendances.edit', [$this->team, 'data' => '2026-10-20']), false);

        $this->get(route('calendar.index', ['settimana' => '2026-10-28']))->assertOk()
            ->assertSee('26 ottobre - 1 novembre 2026')->assertSee('Riunione della prossima settimana');
    }

    public function test_admin_creates_edits_and_deletes_team_events(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('calendar.events.create', ['data' => '2026-10-22']))->assertOk()->assertSee('2026-10-22');
        $this->post(route('calendar.events.store'), [
            'title' => 'Incontro genitori', 'type' => 'genitori', 'team_id' => $this->team->id,
            'date' => '2026-10-22', 'starts_at' => '18:00', 'ends_at' => '19:00', 'location' => 'Salone',
        ])->assertRedirect(route('calendar.index', ['settimana' => '2026-10-19']));

        $event = CalendarEvent::sole();
        $this->assertSame($admin->id, $event->user_id);
        $this->put(route('calendar.events.update', $event), [
            'title' => 'Incontro genitori Under 10', 'type' => 'genitori', 'team_id' => $this->team->id,
            'date' => '2026-10-22', 'starts_at' => '18:00', 'ends_at' => '17:00',
        ])->assertSessionHasErrors('ends_at');

        // L'allenatore della squadra vede l'evento ma non può crearne, modificarlo o eliminarlo.
        $this->actingAs($this->coach)->get(route('calendar.index'))->assertSee('Incontro genitori')->assertSee('18:00 - 19:00')
            ->assertDontSee('Nuovo evento')->assertDontSee(route('calendar.events.edit', $event), false);
        $this->get(route('calendar.events.create'))->assertForbidden();
        $this->post(route('calendar.events.store'), [
            'title' => 'Cena', 'type' => 'altro', 'team_id' => $this->team->id, 'date' => '2026-10-22', 'starts_at' => '20:00',
        ])->assertForbidden();
        $this->get(route('calendar.events.edit', $event))->assertForbidden();

        $this->actingAs($admin)->delete(route('calendar.events.destroy', $event))->assertRedirect();
        $this->assertModelMissing($event);
    }

    public function test_only_admin_manages_club_wide_events(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('calendar.events.store'), [
            'title' => 'Formazione allenatori', 'type' => 'formazione',
            'date' => '2026-10-24', 'starts_at' => '08:30', 'ends_at' => '12:30',
        ])->assertRedirect();
        $event = CalendarEvent::sole();
        $this->assertNull($event->team_id);
        $this->get(route('calendar.index'))->assertOk()->assertSee('Formazione allenatori')->assertSee(route('games.index'), false);

        $this->actingAs($this->coach)->get(route('calendar.index'))->assertSee('Formazione allenatori')
            ->assertDontSee(route('calendar.events.edit', $event), false);
        $this->get(route('calendar.events.edit', $event))->assertForbidden();
        $this->delete(route('calendar.events.destroy', $event))->assertForbidden();
    }
}
