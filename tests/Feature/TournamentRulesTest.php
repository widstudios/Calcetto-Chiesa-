<?php

namespace Tests\Feature;

use App\Enums\TournamentRules;
use App\Models\Tournament;
use App\Models\User;
use App\Support\RulesText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TournamentRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_new_tournaments_use_the_csi_rules(): void
    {
        $this->post(route('tournaments.store'), ['name' => 'Coppa Prova'])->assertRedirect();

        $tournament = Tournament::firstWhere('name', 'Coppa Prova');
        $this->assertSame(TournamentRules::Csi, $tournament->rules_type);

        $this->get(route('home'))->assertOk()->assertSee('Oratorio Cup 2026/2027');
    }

    public function test_admin_writes_own_rules_shown_on_the_public_site(): void
    {
        $this->post(route('tournaments.store'), [
            'name' => 'Coppa Prova',
            'rules_type' => 'proprio',
            'rules_text' => "Si gioca 2 tempi da 20 minuti.\n\n- Massimo 12 in distinta\n- Cambi volanti <b>liberi</b>",
        ])->assertRedirect();

        $this->get(route('home'))->assertOk()
            ->assertSee('Si gioca 2 tempi da 20 minuti.')
            ->assertSee('<li>Massimo 12 in distinta</li>', false)
            ->assertSee('Cambi volanti &lt;b&gt;liberi&lt;/b&gt;', false)
            ->assertDontSee('Oratorio Cup 2026/2027');
    }

    public function test_own_rules_need_text_or_pdf(): void
    {
        $this->post(route('tournaments.store'), ['name' => 'Coppa Prova', 'rules_type' => 'proprio'])
            ->assertSessionHasErrors('rules_text');

        $this->assertDatabaseCount('tournaments', 0);
    }

    public function test_admin_uploads_replaces_and_removes_the_rules_pdf(): void
    {
        $tournament = Tournament::factory()->create(['name' => 'Coppa Prova']);

        $this->put(route('tournaments.update', $tournament), [
            'name' => 'Coppa Prova', 'rules_type' => 'proprio',
            'rules_pdf' => UploadedFile::fake()->create('regole.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('tournaments.index'));

        $first = $tournament->fresh()->rules_pdf;
        Storage::disk('uploads')->assertExists($first);
        $this->get(route('tournaments.edit', $tournament))->assertOk()->assertSee('Apri il PDF caricato')->assertSee('Togli il PDF');
        $this->get(route('tournaments.index'))->assertSee('Proprio');
        $this->get(route('home'))->assertSee('Scarica il regolamento (PDF)')->assertSee('uploads/'.$first);

        // Il PDF già caricato basta: niente testo obbligatorio.
        $this->put(route('tournaments.update', $tournament), ['name' => 'Coppa Prova', 'rules_type' => 'proprio'])
            ->assertSessionHasNoErrors();

        $this->put(route('tournaments.update', $tournament), [
            'name' => 'Coppa Prova', 'rules_type' => 'proprio',
            'rules_pdf' => UploadedFile::fake()->create('nuove.pdf', 200, 'application/pdf'),
        ]);
        $second = $tournament->fresh()->rules_pdf;
        $this->assertNotSame($first, $second);
        Storage::disk('uploads')->assertMissing($first);

        // Togliere l'unico PDF senza testo non si può.
        $this->put(route('tournaments.update', $tournament), ['name' => 'Coppa Prova', 'rules_type' => 'proprio', 'remove_rules_pdf' => 1])
            ->assertSessionHasErrors('rules_text');
        Storage::disk('uploads')->assertExists($second);

        $this->put(route('tournaments.update', $tournament), ['name' => 'Coppa Prova', 'rules_type' => 'proprio', 'rules_text' => 'Regole brevi.', 'remove_rules_pdf' => 1]);
        $this->assertNull($tournament->fresh()->rules_pdf);
        Storage::disk('uploads')->assertMissing($second);
    }

    public function test_only_pdf_files_are_accepted(): void
    {
        $this->post(route('tournaments.store'), [
            'name' => 'Coppa Prova', 'rules_type' => 'proprio',
            'rules_pdf' => UploadedFile::fake()->image('regole.png'),
        ])->assertSessionHasErrors('rules_pdf');
    }

    public function test_deleting_the_tournament_deletes_its_rules_pdf(): void
    {
        Storage::disk('uploads')->put('regolamento-1.pdf', 'x');
        $tournament = Tournament::factory()->create(['rules_type' => 'proprio', 'rules_pdf' => 'regolamento-1.pdf']);

        $this->delete(route('tournaments.destroy', $tournament));

        Storage::disk('uploads')->assertMissing('regolamento-1.pdf');
    }

    public function test_each_tournament_shows_its_own_rules(): void
    {
        Tournament::factory()->create(['name' => 'Coppa Uno', 'starts_on' => '2026-11-01']);
        $own = Tournament::factory()->create(['name' => 'Coppa Due', 'starts_on' => '2026-10-01', 'rules_type' => 'proprio', 'rules_text' => 'Regola speciale della Coppa Due.']);

        $this->get(route('home'))->assertSee('Oratorio Cup 2026/2027')->assertDontSee('Regola speciale della Coppa Due.');
        $this->get(route('home', ['torneo' => $own->id]))->assertSee('Regola speciale della Coppa Due.');
    }

    public function test_rules_text_becomes_paragraphs_and_lists(): void
    {
        $this->assertSame([
            ['type' => 'p', 'lines' => ['Primo paragrafo', 'seconda riga']],
            ['type' => 'ul', 'lines' => ['uno', 'due']],
            ['type' => 'p', 'lines' => ['Dopo']],
            ['type' => 'p', 'lines' => ['Nuovo paragrafo']],
        ], RulesText::blocks("Primo paragrafo\nseconda riga\n- uno\n* due\nDopo\n\nNuovo paragrafo"));
        $this->assertSame([], RulesText::blocks(null));
    }
}
