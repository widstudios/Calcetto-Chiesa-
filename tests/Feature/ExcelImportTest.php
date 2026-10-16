<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Enums\SquadRole;
use App\Imports\SpreadsheetReader;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $values) {
            // Come in Excel, le celle con una data hanno un formato data.
            $dateStyles = array_map(fn () => (new Style)->withFormat('dd/mm/yyyy hh:mm'), array_filter($values, fn ($v) => $v instanceof DateTimeInterface));
            $writer->addRow(Row::fromValuesWithStyles($values, $dateStyles));
        }
        $writer->close();

        return new UploadedFile($path, 'dati.xlsx', null, null, true);
    }

    private function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('dati.csv', $content);
    }

    private function import(string $type, UploadedFile $file): void
    {
        $this->actingAs($this->admin)->post(route('imports.preview', $type), ['file' => $file])
            ->assertOk()
            ->assertSee('Conferma e salva');
        $this->post(route('imports.store', $type))->assertRedirect();
    }

    public function test_only_admin_can_import(): void
    {
        $this->actingAs($this->admin)->get(route('imports.index'))->assertOk()->assertSee('Importa da Excel')->assertSee('Calciatori');

        $coach = User::factory()->create(['role' => Role::Allenatore]);
        $this->actingAs($coach)->get(route('imports.index'))->assertForbidden();
        $this->post(route('imports.preview', 'squadre'), ['file' => $this->csv("torneo;squadra\nA;B\n")])->assertForbidden();
    }

    public function test_template_has_the_columns_of_the_import(): void
    {
        $response = $this->actingAs($this->admin)->get(route('imports.template', 'calciatori'))->assertOk();
        $this->assertStringContainsString('modello-calciatori.xlsx', $response->headers->get('content-disposition'));

        $read = SpreadsheetReader::read($response->getFile()->getPathname(), 'xlsx');
        $this->assertSame(['torneo', 'stagione', 'squadra', 'cognome', 'nome', 'anno', 'numero', 'cellulare', 'cellulare2', 'ruolo'], $read['header']);
        $this->assertCount(2, $read['rows']);
    }

    public function test_imports_a_whole_tournament_from_excel_without_duplicates(): void
    {
        $this->import('tornei', $this->xlsx([
            ['Nome', 'Stagione', 'Inizio', 'Fine', 'Descrizione'],
            ['Torneo di Primavera', '2026/27', new DateTimeImmutable('2027-03-01'), '30/05/2027', 'Calcio a 5'],
        ]));
        $tournament = Tournament::sole();
        $this->assertSame('2027-03-01', $tournament->starts_on->format('Y-m-d'));
        $this->assertSame('2027-05-30', $tournament->ends_on->format('Y-m-d'));

        $coach = User::factory()->create(['email' => 'luca@example.com']);
        $this->import('squadre', $this->csv("torneo;squadra;allenatore;email_allenatore\nTorneo di primavera;Leoni;Mario Rossi;\nTorneo di Primavera;Aquile;;LUCA@example.com\n"));
        $this->assertSame(['Aquile', 'Leoni'], $tournament->teams()->orderBy('name')->pluck('name')->all());
        $this->assertTrue(Team::firstWhere('name', 'Aquile')->coach->is($coach));

        $players = [
            ['torneo', 'squadra', 'cognome', 'nome', 'anno', 'numero', 'ruolo'],
            ['Torneo di Primavera', 'Leoni', 'ROSSI', 'mario', 1995, 10, ''],
            ['Torneo di Primavera', 'Leoni', 'Verdi', 'Paolo', 1998, '', 'Panchina'],
        ];
        $this->import('calciatori', $this->xlsx($players));
        $this->import('calciatori', $this->xlsx($players));
        $this->assertSame(2, Player::count());
        $mario = Player::firstWhere('last_name', 'Rossi');
        $this->assertSame('Mario Rossi', $mario->fullName());
        $this->assertSame(10, $mario->shirt_number);
        $this->assertSame(SquadRole::Panchina, Player::firstWhere('last_name', 'Verdi')->squad_role);

        $games = [
            ['torneo', 'giornata', 'data', 'ora', 'casa', 'trasferta', 'campo', 'gol_casa', 'gol_trasferta'],
            ['Torneo di Primavera', 1, new DateTimeImmutable('2026-10-18 20:30'), '', 'Leoni', 'Aquile', 'Oratorio', 3, 2],
            ['Torneo di Primavera', 2, '25/10/2026', '21:00', 'aquile', 'leoni', '', '', ''],
        ];
        $this->import('partite', $this->xlsx($games));
        $this->import('partite', $this->xlsx($games));
        $this->assertSame(2, Game::count());
        $first = Game::firstWhere('matchday', 1);
        $this->assertSame('2026-10-18 20:30', $first->kickoff_at->format('Y-m-d H:i'));
        $this->assertSame(GameStatus::Giocata, $first->status);
        $this->assertSame([3, 2], [$first->home_score, $first->away_score]);
        $this->assertSame(GameStatus::Programmata, Game::firstWhere('matchday', 2)->status);

        // CSV salvato da Excel per Windows, non in UTF-8.
        $this->import('squadre', $this->csv(mb_convert_encoding("torneo;squadra;allenatore\nTorneo di Primavera;Leoni;Niccolò Bianchi\n", 'Windows-1252', 'UTF-8')));
        $this->assertSame('Niccolò Bianchi', Team::firstWhere('name', 'Leoni')->coach_name);

        $this->import('allenamenti', $this->csv("torneo,squadra,giorno,campo,inizio,fine\nTorneo di Primavera,Leoni,martedi,Oratorio,18.30,20:00\n"));
        $training = Team::firstWhere('name', 'Leoni')->trainings()->sole();
        $this->assertSame('Martedì · 18:30 - 20:00', $training->weekdayName().' · '.$training->timeRange());
    }

    public function test_coaches_import_keeps_existing_registration(): void
    {
        $coach = User::factory()->create(['email' => 'simone@example.com', 'csi_registered_on' => '2026-09-28']);

        $this->actingAs($this->admin)->post(route('imports.preview', 'allenatori'), [
            'file' => $this->csv("nome;email;tesserato_il\nSimone Poggi;Simone@example.com;\nPiero Perilli;piero@example.com;\n"),
        ])->assertOk()->assertSee('Aggiorna')->assertSee('Nuovo');
        $this->post(route('imports.store', 'allenatori'))
            ->assertRedirect(route('coaches.index'))
            ->assertSessionHas('status', 'Allenatori da Excel: 1 aggiunti, 1 aggiornati.');

        $this->assertSame('2026-09-28', $coach->fresh()->csi_registered_on->format('Y-m-d'));
        $this->assertSame(Role::Allenatore, User::firstWhere('email', 'piero@example.com')->role);
    }

    public function test_bad_rows_are_listed_and_nothing_is_saved(): void
    {
        $this->actingAs($this->admin)->post(route('imports.preview', 'calciatori'), [
            'file' => $this->csv("torneo;squadra;cognome;nome;anno;ruolo\nUnder 14;Under 14;Rossi;Mario;2099;\nUnder 14;Under 14;Bianchi;;2013;\nUnder 14;Under 14;Verdi;Luca;2013;portiere\n"),
        ])
            ->assertOk()
            ->assertSee('3 righe da correggere')
            ->assertSee('Riga 2')
            ->assertSee('Manca &quot;nome&quot;', false)
            ->assertSee('ruolo')
            ->assertDontSee('Conferma e salva');

        // Senza un'anteprima valida la conferma non salva niente.
        $this->post(route('imports.store', 'calciatori'))->assertRedirect(route('imports.index'))->assertSessionHasErrors('file');
        $this->assertSame(0, Tournament::count());
    }

    public function test_games_need_teams_already_in_the_tournament(): void
    {
        Team::factory()->create(['name' => 'Leoni', 'tournament_id' => Tournament::factory()->create(['name' => 'Primavera'])]);

        $this->actingAs($this->admin)->post(route('imports.preview', 'partite'), [
            'file' => $this->csv("torneo;casa;trasferta\nPrimavera;Leoni;Tigri\n"),
        ])->assertOk()->assertSee('La squadra &quot;Tigri&quot; non c&#039;è nel torneo', false);
        $this->assertSame(0, Game::count());
    }

    public function test_missing_columns_are_reported(): void
    {
        $this->actingAs($this->admin)
            ->from(route('imports.index'))
            ->post(route('imports.preview', 'calciatori'), ['file' => $this->csv("cognome;nome\nRossi;Mario\n")])
            ->assertRedirect(route('imports.index'))
            ->assertSessionHasErrors(['file' => 'Nella prima riga mancano le colonne: torneo, squadra, anno. Parti dal modello da scaricare.']);
    }
}
