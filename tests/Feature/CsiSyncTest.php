<?php

namespace Tests\Feature;

use App\Models\CsiDocument;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsiSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.csi.google_key' => 'chiave-di-prova', 'services.csi.drive_folder' => 'cartella']);
    }

    /** @param  list<array<string, string>>  $files  per riferimento, così il test può cambiare la cartella */
    private function fakeDrive(array &$files): void
    {
        Http::fake(function ($request) use (&$files) {
            if (str_contains($request->url(), 'alt=media')) {
                return Http::response('%PDF-1.7 '.basename(parse_url($request->url(), PHP_URL_PATH)));
            }

            return Http::response(['files' => $files]);
        });
    }

    public function test_command_downloads_new_files_and_skips_unchanged_ones(): void
    {
        $files = [
            ['id' => 'cu1', 'name' => 'Comunicato n. 1.pdf', 'mimeType' => 'application/pdf', 'size' => '2048', 'modifiedTime' => '2026-10-22T18:00:00.290Z'],
            ['id' => 'sub', 'name' => 'Archivio', 'mimeType' => 'application/vnd.google-apps.folder', 'modifiedTime' => '2026-10-01T10:00:00Z'],
        ];
        $this->fakeDrive($files);

        $this->artisan('csi:sync')->expectsOutputToContain('Nuovi: 1')->assertSuccessful();

        $document = CsiDocument::sole();
        $this->assertSame('Comunicato n. 1.pdf', $document->name);
        Storage::disk('local')->assertExists($document->path);
        $this->assertStringContainsString('cu1', Storage::disk('local')->get($document->path));
        $this->assertNotNull(Setting::get('csi_synced_at'));

        // Secondo giro senza cambiamenti: niente download.
        $this->artisan('csi:sync')->expectsOutputToContain('Nuovi: 0. Aggiornati: 0')->assertSuccessful();
        Http::assertSentCount(3);

        // Il CSI ricarica il comunicato corretto: si scarica di nuovo.
        $files[0]['modifiedTime'] = '2026-10-23T09:00:00Z';
        $this->artisan('csi:sync')->expectsOutputToContain('Aggiornati: 1')->assertSuccessful();
        $this->assertSame(1, CsiDocument::count());
    }

    public function test_admin_syncs_from_panel_and_opens_pdf(): void
    {
        $files = [['id' => 'cu1', 'name' => 'CU 1.pdf', 'mimeType' => 'application/pdf', 'size' => '2048', 'modifiedTime' => '2026-10-22T18:00:00Z']];
        $this->fakeDrive($files);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/comunicati-csi')->assertOk()->assertSee('Sincronizza ora')->assertSee('Nessun documento');
        $this->actingAs($admin)->post('/comunicati-csi/sincronizza')->assertRedirect('/comunicati-csi')->assertSessionHas('status', '1 file nuovo scaricati dal CSI.');

        $document = CsiDocument::sole();
        $this->actingAs($admin)->get('/comunicati-csi')->assertSee('CU 1.pdf')->assertSee('Pubblicato il 22/10/2026');
        $this->actingAs($admin)->get('/comunicati-csi/'.$document->id)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_only_admin_sees_communications(): void
    {
        $this->get('/comunicati-csi')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/comunicati-csi')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/comunicati-csi/sincronizza')->assertForbidden();
    }

    public function test_rejected_key_is_explained_and_remembered(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'API key not valid.']], 400)]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/comunicati-csi/sincronizza')->assertSessionHasErrors('sync');
        $this->assertStringContainsString('chiave API', Setting::get('csi_sync_error'));
        $this->artisan('csi:sync')->assertFailed();
    }

    public function test_missing_key_is_shown(): void
    {
        config(['services.csi.google_key' => null]);
        $this->actingAs(User::factory()->admin()->create())->get('/comunicati-csi')->assertSee('GOOGLE_API_KEY')->assertDontSee('Sincronizza ora');
        $this->artisan('csi:sync')->assertFailed();
    }
}
