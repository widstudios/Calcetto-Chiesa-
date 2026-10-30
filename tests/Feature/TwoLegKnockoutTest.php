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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TwoLegKnockoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function team(string $name): Team
    {
        return Team::firstWhere('name', $name);
    }

    /** Tutte le partite senza fase finale giocate: vince la prima squadra in ordine di nome, per avere classifiche prevedibili. */
    private function playGroupGames(Tournament $tournament): void
    {
        foreach ($tournament->games()->whereNull('stage')->with(['homeTeam', 'awayTeam'])->get() as $game) {
            $homeFirst = strcmp($game->homeTeam->name, $game->awayTeam->name) < 0;
            $game->update(['status' => 'giocata', 'home_score' => $homeFirst ? 1 : 0, 'away_score' => $homeFirst ? 0 : 1]);
        }
    }

    private function saveResult(Game $game, int $home, int $away, array $extra = []): TestResponse
    {
        return $this->put(route('games.update', $game), [
            'home_team_id' => $game->home_team_id, 'away_team_id' => $game->away_team_id, 'stage' => $game->stage->value,
            'status' => 'giocata', 'home_score' => $home, 'away_score' => $away,
        ] + $extra);
    }

    public function test_semifinals_with_two_legs_then_single_final(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('tournaments.store'), [
            'name' => 'Coppa Finta', 'format' => 'gironi', 'groups_count' => 2, 'qualifiers_per_group' => 2,
            'two_leg_stages' => ['semifinale'],
        ])->assertRedirect();
        $tournament = Tournament::firstWhere('name', 'Coppa Finta');
        $this->assertTrue($tournament->isTwoLeg(GameStage::Semifinale));
        $this->assertFalse($tournament->isTwoLeg(GameStage::Finale));

        foreach (['A' => ['Alfa', 'Beta', 'Gamma'], 'B' => ['Delta', 'Epsilon', 'Zeta']] as $group => $names) {
            foreach ($names as $name) {
                Team::factory()->for($tournament)->create(['name' => $name, 'group_name' => $group]);
            }
        }
        RoundRobin::generate($tournament);
        $this->playGroupGames($tournament);

        $this->post(route('games.knockout', $tournament))->assertSessionHasNoErrors();

        // Due semifinali, ognuna con andata e ritorno: 1ª A - 2ª B e 1ª B - 2ª A.
        $semis = $tournament->knockoutGames()->with(['homeTeam', 'awayTeam'])->orderBy('bracket_slot')->orderBy('leg')->get();
        $this->assertCount(4, $semis);
        $this->assertSame(['Alfa-Epsilon', 'Alfa-Epsilon', 'Delta-Beta', 'Delta-Beta'], $semis->map(fn ($g) => $g->homeTeam->name.'-'.$g->awayTeam->name)->all());
        $this->assertSame([1, 2, 1, 2], $semis->pluck('leg')->all());
        $this->assertSame('Semifinale · andata', $semis[0]->stageLabel());

        // Semifinale 1: 2-0 all'andata, 0-1 al ritorno: passa Alfa (2-1), senza rigori.
        $this->saveResult($semis[0], 2, 0);
        $this->saveResult($semis[1], 0, 1);
        $this->assertSame($this->team('Alfa')->id, Knockout::tieWinner($semis->where('bracket_slot', 1)->map->fresh()));

        // Semifinale 2: 1-0 e 0-1, gol pari: al ritorno servono i rigori.
        $this->saveResult($semis[2], 1, 0);
        $this->saveResult($semis[3], 0, 1)->assertSessionHasErrors('home_penalties');
        $this->assertNull($tournament->knockoutGames()->where('stage', 'finale')->first());
        $this->saveResult($semis[3], 0, 1, ['home_penalties' => 3, 'away_penalties' => 4])->assertSessionHasNoErrors();

        // All'andata i rigori non si salvano mai.
        $this->assertNull($semis[2]->fresh()->home_penalties);

        // Finale secca tra le vincenti.
        $final = $tournament->knockoutGames()->where('stage', 'finale')->with(['homeTeam', 'awayTeam'])->get();
        $this->assertCount(1, $final);
        $this->assertNull($final[0]->leg);
        $this->assertSame('Alfa-Beta', $final[0]->homeTeam->name.'-'.$final[0]->awayTeam->name);

        $this->get('/?torneo='.$tournament->id)->assertOk()->assertSee('Andata e ritorno')->assertSee('Fase finale');
    }

    public function test_single_group_league_with_two_leg_final(): void
    {
        $tournament = Tournament::factory()->create([
            'format' => TournamentFormat::Campionato, 'qualifiers_per_group' => 2, 'two_leg_stages' => ['finale'],
        ]);
        foreach (['Alfa', 'Beta', 'Gamma', 'Delta', 'Epsilon'] as $name) {
            Team::factory()->for($tournament)->create(['name' => $name]);
        }
        RoundRobin::generate($tournament);
        $this->playGroupGames($tournament);

        $this->actingAs($this->admin)->get(route('games.index', ['tournament' => $tournament->id]))->assertSee('Crea fase finale');
        $this->post(route('games.knockout', $tournament))->assertSessionHasNoErrors();

        // Le prime due del girone unico in finale, andata e ritorno.
        $final = $tournament->knockoutGames()->with(['homeTeam', 'awayTeam'])->orderBy('leg')->get();
        $this->assertSame([GameStage::Finale, GameStage::Finale], $final->pluck('stage')->all());
        $this->assertSame(['Alfa-Beta', 'Alfa-Beta'], $final->map(fn ($g) => $g->homeTeam->name.'-'.$g->awayTeam->name)->all());

        // Il ritorno si inserisce dopo l'andata.
        $this->saveResult($final[1], 0, 2)->assertSessionHasErrors('home_score');
        $this->saveResult($final[0], 1, 1);
        $this->saveResult($final[1], 0, 2)->assertSessionHasNoErrors();

        // Correggere l'andata non può lasciare il totale pari senza rigori (3-1 e 0-2 fa 3-3).
        $this->saveResult($final[0], 3, 1)->assertSessionHasErrors('home_score');
        $this->assertSame(1, $final[0]->fresh()->home_score);

        $this->assertSame($this->team('Beta')->id, Knockout::tieWinner($final->map->fresh()));
        $this->get('/?torneo='.$tournament->id)->assertOk()->assertSee('Vince il torneo');
    }

    public function test_correcting_a_semifinal_does_not_split_a_started_two_leg_final(): void
    {
        $tournament = Tournament::factory()->create([
            'format' => TournamentFormat::Campionato, 'qualifiers_per_group' => 4, 'two_leg_stages' => ['finale'],
        ]);
        foreach (['Alfa', 'Beta', 'Gamma', 'Delta'] as $name) {
            Team::factory()->for($tournament)->create(['name' => $name]);
        }
        RoundRobin::generate($tournament);
        $this->playGroupGames($tournament);
        $this->actingAs($this->admin)->post(route('games.knockout', $tournament))->assertSessionHasNoErrors();

        $semis = $tournament->knockoutGames()->orderBy('bracket_slot')->get();
        $this->saveResult($semis[0], 1, 0);
        $this->saveResult($semis[1], 1, 0);
        $final = $tournament->knockoutGames()->where('stage', 'finale')->orderBy('leg')->get();
        $this->assertCount(2, $final);
        $this->saveResult($final[0], 2, 2);

        // L'andata della finale è giocata: cambiare una semifinale non cambia le squadre del ritorno.
        $this->saveResult($semis[0], 0, 1);
        $this->assertSame($final[0]->home_team_id, $final[1]->fresh()->home_team_id);
        $this->assertSame($final[0]->away_team_id, $final[1]->fresh()->away_team_id);
    }

    public function test_team_import_sets_the_group(): void
    {
        $file = UploadedFile::fake()->createWithContent('squadre.csv', "torneo;squadra;girone\nTorneo Inventato;Alfa;A\nTorneo Inventato;Beta;Girone B\nTorneo Inventato;Gamma;\n");

        $this->actingAs($this->admin)->post(route('imports.preview', 'squadre'), ['file' => $file])->assertOk();
        $this->post(route('imports.store', 'squadre'))->assertRedirect();

        $tournament = Tournament::firstWhere('name', 'Torneo Inventato');
        $this->assertTrue($tournament->hasGroups());
        $this->assertSame(['A', 'B'], $tournament->groupNames());
        $this->assertSame(['A', 'B', null], [$this->team('Alfa')->group_name, $this->team('Beta')->group_name, $this->team('Gamma')->group_name]);

        $bad = UploadedFile::fake()->createWithContent('squadre.csv', "torneo;squadra;girone\nTorneo Inventato;Delta;Z\n");
        $this->post(route('imports.preview', 'squadre'), ['file' => $bad])->assertSee('non valido: scrivi una lettera da A a H');
    }
}
