<?php

namespace Tests\Feature;

use App\Enums\GameStage;
use App\Enums\TournamentFormat;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\Knockout;
use App\Support\RoundRobin;
use App\Support\TournamentStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentGroupsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Torneo a gironi con le squadre già divise: $groups è ['A' => ['Alfa', 'Beta'], ...].
     *
     * @param  array<string, list<string>>  $groups
     */
    private function groupTournament(array $groups, int $qualifiers = 2): Tournament
    {
        $tournament = Tournament::factory()->create([
            'format' => TournamentFormat::Gironi, 'groups_count' => count($groups), 'qualifiers_per_group' => $qualifiers,
        ]);
        foreach ($groups as $group => $names) {
            foreach ($names as $name) {
                Team::factory()->for($tournament)->create(['name' => $name, 'group_name' => $group]);
            }
        }

        return $tournament;
    }

    private function team(string $name): Team
    {
        return Team::firstWhere('name', $name);
    }

    /** Segna come giocata la partita tra due squadre (in qualunque campo) con il risultato della prima. */
    private function play(string $a, string $b, int $goalsA, int $goalsB, array $extra = []): Game
    {
        $game = Game::query()
            ->where(fn ($q) => $q->where('home_team_id', $this->team($a)->id)->where('away_team_id', $this->team($b)->id))
            ->orWhere(fn ($q) => $q->where('home_team_id', $this->team($b)->id)->where('away_team_id', $this->team($a)->id))
            ->latest('id')->firstOrFail();
        $home = $game->home_team_id === $this->team($a)->id;
        $game->update(['status' => 'giocata', 'home_score' => $home ? $goalsA : $goalsB, 'away_score' => $home ? $goalsB : $goalsA] + $extra);

        return $game;
    }

    public function test_admin_chooses_groups_when_creating_a_tournament(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('tournaments.store'), ['name' => 'Coppa Inventata', 'format' => 'gironi', 'groups_count' => 2, 'qualifiers_per_group' => 2])
            ->assertRedirect();
        $tournament = Tournament::firstWhere('name', 'Coppa Inventata');
        $this->assertTrue($tournament->hasGroups());
        $this->assertSame(['A', 'B'], $tournament->groupNames());

        $teams = Team::factory()->count(2)->for($tournament)->create();
        $this->get(route('tournaments.edit', $tournament))->assertOk()->assertSee('Squadre nei gironi');
        $this->put(route('tournaments.update', $tournament), [
            'name' => 'Coppa Inventata', 'format' => 'gironi', 'groups_count' => 2, 'qualifiers_per_group' => 2,
            'groups' => [$teams[0]->id => 'A', $teams[1]->id => 'B'],
        ])->assertRedirect(route('tournaments.index'));
        $this->assertSame(['A', 'B'], $teams->map->fresh()->pluck('group_name')->all());

        // Tornando a campionato i gironi spariscono.
        $this->put(route('tournaments.update', $tournament), ['name' => 'Coppa Inventata', 'format' => 'campionato', 'groups_count' => 2]);
        $this->assertNull($tournament->fresh()->groups_count);
        $this->assertSame([null, null], $teams->map->fresh()->pluck('group_name')->all());
    }

    public function test_tournaments_created_without_format_are_leagues(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('tournaments.store'), ['name' => 'Campionato Finto'])
            ->assertRedirect(route('tournaments.index'));

        $this->assertSame(TournamentFormat::Campionato, Tournament::firstWhere('name', 'Campionato Finto')->format);
    }

    public function test_team_group_must_exist_in_its_tournament(): void
    {
        $tournament = $this->groupTournament(['A' => [], 'B' => []]);
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('teams.store'), ['tournament_id' => $tournament->id, 'name' => 'Squadra Zeta', 'group_name' => 'C'])
            ->assertSessionHasErrors('group_name');
        $this->post(route('teams.store'), ['tournament_id' => $tournament->id, 'name' => 'Squadra Zeta', 'group_name' => 'B'])
            ->assertRedirect();
        $this->assertSame('B', $this->team('Squadra Zeta')->group_name);
    }

    public function test_round_robin_pairs_every_team_once(): void
    {
        $rounds = RoundRobin::rounds([1, 2, 3, 4, 5]);

        $this->assertCount(5, $rounds);
        $pairs = collect($rounds)->flatten(1)->map(fn ($p) => min($p).'-'.max($p));
        $this->assertCount(10, $pairs);
        $this->assertSame(10, $pairs->unique()->count());
    }

    public function test_calendar_is_generated_inside_each_group(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta', 'Gamma'], 'B' => ['Delta', 'Epsilon', 'Zeta', 'Eta']]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('games.generate', $tournament))
            ->assertSessionHas('status', 'Create 9 partite. Ora puoi mettere data, ora e campo di ognuna.');

        $this->assertSame(9, $tournament->games()->count());
        $tournament->games()->with(['homeTeam', 'awayTeam'])->get()
            ->each(fn ($g) => $this->assertSame($g->homeTeam->group_name, $g->awayTeam->group_name));

        // Una seconda volta non crea doppioni.
        $this->assertSame(0, RoundRobin::generate($tournament));
        $this->assertSame(9, RoundRobin::generate($tournament, true));
    }

    public function test_each_group_has_its_own_standings(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta'], 'B' => ['Gamma', 'Delta']]);
        RoundRobin::generate($tournament);
        $this->play('Beta', 'Alfa', 2, 0);
        $this->play('Gamma', 'Delta', 1, 1);
        // Un'amichevole tra gironi diversi non conta.
        Game::factory()->for($tournament)->played(5, 0)->create(['home_team_id' => $this->team('Alfa')->id, 'away_team_id' => $this->team('Delta')->id, 'matchday' => null]);

        $groups = (new TournamentStats($tournament))->groupStandings();

        $this->assertSame(['A', 'B'], $groups->keys()->all());
        $this->assertSame(['Beta', 'Alfa'], $groups['A']->pluck('team.name')->all());
        $this->assertSame([3, 0], $groups['A']->pluck('points')->all());
        $this->assertSame([1, 1], $groups['B']->pluck('points')->all());

        $this->get('/?torneo='.$tournament->id)->assertOk()
            ->assertSee('Classifiche dei gironi')->assertSee('Girone A')->assertSee('Girone B');
        $this->get(route('standings.pdf', $tournament))->assertOk();
    }

    public function test_knockout_follows_the_groups_up_to_the_final(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta', 'Gamma'], 'B' => ['Delta', 'Epsilon', 'Zeta']]);
        RoundRobin::generate($tournament);
        $admin = User::factory()->admin()->create();

        // Con partite ancora da giocare la fase finale non parte.
        $this->actingAs($admin)->post(route('games.knockout', $tournament))->assertSessionHasErrors('knockout');

        $this->play('Alfa', 'Beta', 2, 0);
        $this->play('Alfa', 'Gamma', 2, 0);
        $this->play('Beta', 'Gamma', 1, 0);
        $this->play('Delta', 'Epsilon', 3, 0);
        $this->play('Delta', 'Zeta', 3, 0);
        $this->play('Epsilon', 'Zeta', 1, 0);

        $this->post(route('games.knockout', $tournament))->assertSessionHasNoErrors();

        // 1ª del girone A contro 2ª del B e viceversa.
        $semis = $tournament->knockoutGames()->with(['homeTeam', 'awayTeam'])->get();
        $this->assertSame([GameStage::Semifinale, GameStage::Semifinale], $semis->pluck('stage')->all());
        $this->assertSame(['Alfa-Epsilon', 'Delta-Beta'], $semis->map(fn ($g) => $g->homeTeam->name.'-'.$g->awayTeam->name)->all());

        // Un pareggio nella fase finale vuole i rigori.
        $this->put(route('games.update', $semis[0]), [
            'home_team_id' => $semis[0]->home_team_id, 'away_team_id' => $semis[0]->away_team_id, 'stage' => 'semifinale',
            'status' => 'giocata', 'home_score' => 1, 'away_score' => 1,
        ])->assertSessionHasErrors('home_penalties');
        $this->put(route('games.update', $semis[0]), [
            'home_team_id' => $semis[0]->home_team_id, 'away_team_id' => $semis[0]->away_team_id, 'stage' => 'semifinale',
            'status' => 'giocata', 'home_score' => 1, 'away_score' => 1, 'home_penalties' => 3, 'away_penalties' => 4,
        ])->assertSessionHasNoErrors();
        $this->assertNull($tournament->knockoutGames()->where('stage', 'finale')->first());

        $this->put(route('games.update', $semis[1]), [
            'home_team_id' => $semis[1]->home_team_id, 'away_team_id' => $semis[1]->away_team_id, 'stage' => 'semifinale',
            'status' => 'giocata', 'home_score' => 2, 'away_score' => 0,
        ])->assertSessionHasNoErrors();

        // Finita la semifinale, la finale si crea da sola con le vincenti.
        $final = $tournament->knockoutGames()->where('stage', 'finale')->with(['homeTeam', 'awayTeam'])->first();
        $this->assertSame('Epsilon-Delta', $final->homeTeam->name.'-'.$final->awayTeam->name);

        // Il risultato corretto aggiorna la finale ancora da giocare.
        $this->put(route('games.update', $semis[0]), [
            'home_team_id' => $semis[0]->home_team_id, 'away_team_id' => $semis[0]->away_team_id, 'stage' => 'semifinale',
            'status' => 'giocata', 'home_score' => 3, 'away_score' => 1,
        ]);
        $this->assertSame('Alfa', $final->fresh()->homeTeam->name);

        // La fase finale non cambia la classifica dei gironi.
        $this->assertSame([6, 3, 0], (new TournamentStats($tournament))->groupStandings()['A']->pluck('points')->all());

        $this->play('Alfa', 'Delta', 2, 1);
        $this->get('/?torneo='.$tournament->id)->assertOk()->assertSee('Fase finale')->assertSee('Vince il torneo');
        $this->get(route('games.index', ['tournament' => $tournament->id]))->assertOk()->assertSee('Semifinale');
        $this->get(route('games.edit', $final))->assertOk();
    }

    public function test_knockout_needs_a_power_of_two(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta'], 'B' => ['Gamma', 'Delta'], 'C' => ['Eta', 'Theta']], 1);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('games.knockout', $tournament))
            ->assertSessionHasErrors(['knockout' => 'Passano il turno 3 squadre: per un tabellone servono 2, 4, 8 o 16 squadre. Cambia quante passano per girone.']);
    }

    public function test_knockout_avoids_same_group_in_first_round(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta', 'Gamma', 'Delta']], 4);

        Knockout::start($tournament);

        // Un solo girone: 1ª contro 4ª e 2ª contro 3ª.
        $this->assertSame(2, $tournament->knockoutGames()->count());
    }

    public function test_head_to_head_breaks_ties_before_goal_difference(): void
    {
        $tournament = $this->groupTournament(['A' => ['Alfa', 'Beta', 'Gamma']]);
        RoundRobin::generate($tournament);
        $this->play('Beta', 'Alfa', 1, 0);
        $this->play('Alfa', 'Gamma', 5, 0);
        $this->play('Gamma', 'Beta', 1, 0);

        // Tutte a 3 punti e scontri diretti pari: decide la differenza reti.
        $this->assertSame(['Alfa', 'Beta', 'Gamma'], (new TournamentStats($tournament))->groupStandings()['A']->pluck('team.name')->all());

        $other = $this->groupTournament(['A' => ['Delta', 'Epsilon', 'Zeta', 'Eta']]);
        RoundRobin::generate($other);
        $this->play('Epsilon', 'Delta', 1, 0);
        $this->play('Delta', 'Zeta', 6, 0);
        $this->play('Delta', 'Eta', 6, 0);
        $this->play('Zeta', 'Epsilon', 1, 0);
        $this->play('Epsilon', 'Eta', 1, 0);

        // Delta ed Epsilon a pari punti: Epsilon ha vinto lo scontro diretto anche con differenza reti peggiore.
        $standings = (new TournamentStats($other))->standings();
        $this->assertSame(['Epsilon', 'Delta'], $standings->take(2)->pluck('team.name')->all());
        $this->assertSame([6, 6], $standings->take(2)->pluck('points')->all());
    }

    public function test_calendar_gets_times_on_one_field_shared_by_tournaments(): void
    {
        $younger = $this->groupTournament(['A' => ['Alfa', 'Beta', 'Gamma'], 'B' => ['Delta', 'Epsilon', 'Zeta']]);
        $older = $this->groupTournament(['A' => ['Eta', 'Theta', 'Iota'], 'B' => ['Kappa', 'Lambda', 'Mu']]);
        $this->actingAs(User::factory()->admin()->create());
        $schedule = ['first_day' => '2030-11-10', 'from' => '15:00', 'to' => '19:00', 'minutes' => 20, 'venue' => 'Campo inventato'];

        $this->post(route('games.generate', $younger), $schedule)->assertSessionHasNoErrors();
        $this->post(route('games.generate', $older), $schedule)->assertSessionHasNoErrors();

        // 12 partite da 20 minuti riempiono il pomeriggio dalle 15 alle 19, senza sovrapposizioni.
        $times = Game::orderBy('kickoff_at')->pluck('kickoff_at')->map->format('H:i');
        $this->assertSame(['15:00', '15:20', '15:40', '16:00', '16:20', '16:40', '17:00', '17:20', '17:40', '18:00', '18:20', '18:40'], $times->all());
        $this->assertSame(['Campo inventato'], Game::distinct()->pluck('venue')->all());
        // Il primo torneo gioca prima, alternando i gironi.
        $first = $younger->games()->with(['homeTeam', 'awayTeam'])->orderBy('kickoff_at')->get();
        $this->assertSame(['A', 'B', 'A', 'B', 'A', 'B'], $first->map->groupName()->all());

        // Con il campo pieno si passa alla settimana dopo.
        $third = $this->groupTournament(['A' => ['Nu', 'Xi']]);
        $this->post(route('games.generate', $third), $schedule);
        $this->assertSame('2030-11-17 15:00', $third->games()->first()->kickoff_at->format('Y-m-d H:i'));

        $this->post(route('games.generate', $third), ['first_day' => '2030-11-10', 'from' => '19:00', 'to' => '15:00', 'minutes' => 20])
            ->assertSessionHasErrors('to');
    }
}
