<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Lineup;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LineupLimitPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-16 18:00');
        $this->coach = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->team->coaches()->attach($this->coach);
    }

    /**
     * @return array<int, array{called: string}>
     */
    private function callUp(int $count): array
    {
        return Player::factory()->for($this->team)->count($count)->create()
            ->mapWithKeys(fn (Player $p) => [$p->id => ['called' => '1']])->all();
    }

    public function test_lineup_respects_the_rulebook_limit(): void
    {
        $this->assertSame(15, $this->team->tournament->lineupMaxPlayers());
        $players = $this->callUp(16);

        $this->actingAs($this->coach)->get(route('teams.lineups.create', $this->team))->assertOk()->assertSee('convocati su 15 ammessi');
        $this->post(route('teams.lineups.store', $this->team), ['players' => $players])
            ->assertSessionHasErrors(['players' => 'Il regolamento ammette al massimo 15 giocatori in distinta: ne hai spuntati 16. Togli un convocato.']);
        $this->assertSame(0, Lineup::count());

        $this->post(route('teams.lineups.store', $this->team), ['players' => array_slice($players, 0, 15, true)])->assertSessionHasNoErrors();
        $this->assertSame(1, Lineup::count());
    }

    public function test_admin_sets_general_and_category_limits(): void
    {
        $admin = User::factory()->admin()->create();
        $players = $this->callUp(13);

        $this->actingAs($admin)->put(route('settings.update'), ['lineup_max_players' => 12])->assertSessionHasNoErrors();
        $this->assertSame(12, Setting::lineupMaxPlayers());
        $this->post(route('teams.lineups.store', $this->team), ['players' => $players])->assertSessionHasErrors('players');

        // Un limite proprio della categoria vale più di quello generale.
        $tournament = $this->team->tournament;
        $this->put(route('tournaments.update', $tournament), ['name' => $tournament->name, 'lineup_max_players' => 14])->assertSessionHasNoErrors();
        $this->post(route('teams.lineups.store', $this->team), ['players' => $players])->assertSessionHasNoErrors();

        // Tornare a 15 riporta al valore del regolamento.
        $this->put(route('settings.update'), ['lineup_max_players' => 15]);
        $this->assertNull(Setting::get('lineup_max_players'));
        $this->put(route('settings.update'), ['lineup_max_players' => 0])->assertSessionHasErrors('lineup_max_players');
    }

    public function test_pdf_exports(): void
    {
        $players = $this->callUp(3);
        $this->team->trainings()->create(['weekday' => 3, 'field' => 'Oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $this->actingAs($this->coach)->put(route('teams.attendances.update', $this->team), [
            'data' => '2026-10-14',
            'players' => collect($players)->map(fn () => '1')->all(),
        ]);
        $this->post(route('teams.lineups.store', $this->team), ['players' => $players, 'opponent' => 'San Giuseppe']);
        $lineup = Lineup::firstOrFail();

        foreach ([
            route('teams.lineups.pdf', [$this->team, $lineup]),
            route('teams.attendances.pdf', $this->team),
            route('teams.pdf', $this->team),
            route('standings.pdf', $this->team->tournament),
        ] as $url) {
            $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent(), $url);
        }

        Game::factory()->create(['tournament_id' => $this->team->tournament_id, 'home_team_id' => $this->team->id]);
        $this->get(route('games.pdf', $this->team->tournament))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('games.pdf', $this->team->tournament))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_only_for_own_teams(): void
    {
        $other = Team::factory()->create();

        $this->actingAs($this->coach)->get(route('teams.attendances.pdf', $other))->assertForbidden();
        $this->get(route('teams.pdf', $other))->assertForbidden();
    }
}
