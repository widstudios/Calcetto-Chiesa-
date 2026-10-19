<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Document::DISK);
        $this->coach = User::factory()->create(['name' => 'Mister Rossi']);
    }

    public function test_coach_uploads_document_for_everyone(): void
    {
        $this->actingAs($this->coach)->get(route('documents.create'))->assertOk()->assertSee('Chi può vederlo');

        $this->post(route('documents.store'), [
            'title' => 'Modulo iscrizione',
            'file' => UploadedFile::fake()->create('modulo.pdf', 120, 'application/pdf'),
            'visibility' => 'tutti',
        ])->assertRedirect(route('documents.index'));

        $document = Document::sole();
        $this->assertTrue($document->for_everyone);
        $this->assertSame('modulo.pdf', $document->original_name);
        Storage::disk(Document::DISK)->assertExists($document->path);
        // Il file non finisce nella cartella pubblica.
        $this->assertStringStartsWith('documenti/', $document->path);

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('documents.index'))->assertOk()->assertSee('Modulo iscrizione')->assertSee('Mister Rossi');
        $this->get(route('documents.download', [$document, 'scarica' => 1]))->assertOk()->assertDownload('modulo.pdf');
        // Chi non l'ha caricato non lo modifica.
        $this->get(route('documents.edit', $document))->assertForbidden();
        $this->delete(route('documents.destroy', $document))->assertForbidden();
    }

    public function test_only_chosen_people_see_a_private_document(): void
    {
        $chosen = User::factory()->create();
        $excluded = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->coach)->post(route('documents.store'), [
            'title' => 'Certificati medici',
            'file' => UploadedFile::fake()->create('certificati.xlsx', 50),
            'visibility' => 'scelta',
            'viewers' => [$chosen->id],
        ])->assertRedirect();
        $document = Document::sole();
        $this->assertFalse($document->for_everyone);

        $this->get(route('documents.index'))->assertSee('Certificati medici');
        $this->actingAs($chosen)->get(route('documents.index'))->assertSee('Certificati medici');
        $this->get(route('documents.download', $document))->assertOk();
        $this->actingAs($admin)->get(route('documents.index'))->assertSee('Certificati medici');
        $this->get(route('documents.download', $document))->assertOk();

        $this->actingAs($excluded)->get(route('documents.index'))->assertOk()->assertDontSee('Certificati medici');
        $this->get(route('documents.download', $document))->assertForbidden();

        auth()->logout();
        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
    }

    public function test_choosing_people_requires_at_least_one_and_a_file(): void
    {
        $this->actingAs($this->coach)->post(route('documents.store'), [
            'title' => 'Senza persone',
            'visibility' => 'scelta',
        ])->assertSessionHasErrors(['file', 'viewers']);

        $this->post(route('documents.store'), [
            'title' => 'Programma',
            'file' => UploadedFile::fake()->create('virus.exe', 10),
            'visibility' => 'tutti',
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, Document::count());
    }

    public function test_author_changes_visibility_replaces_file_and_deletes(): void
    {
        $chosen = User::factory()->create();
        $this->actingAs($this->coach)->post(route('documents.store'), [
            'title' => 'Orari',
            'file' => UploadedFile::fake()->create('orari.pdf', 10, 'application/pdf'),
            'visibility' => 'tutti',
        ]);
        $document = Document::sole();
        $oldPath = $document->path;

        $this->get(route('documents.edit', $document))->assertOk()->assertSee('orari.pdf');
        $this->put(route('documents.update', $document), [
            'title' => 'Orari nuovi',
            'file' => UploadedFile::fake()->create('orari-2.pdf', 10, 'application/pdf'),
            'visibility' => 'scelta',
            'viewers' => [$chosen->id],
        ])->assertRedirect(route('documents.index'));

        $document->refresh();
        $this->assertSame('Orari nuovi', $document->title);
        $this->assertSame('orari-2.pdf', $document->original_name);
        $this->assertSame([$chosen->id], $document->viewers->pluck('id')->all());
        Storage::disk(Document::DISK)->assertMissing($oldPath);

        // Tornando a "Tutti" le persone scelte si azzerano.
        $this->put(route('documents.update', $document), ['title' => 'Orari nuovi', 'visibility' => 'tutti']);
        $this->assertCount(0, $document->fresh()->viewers);

        $path = $document->path;
        $this->delete(route('documents.destroy', $document))->assertRedirect(route('documents.index'));
        $this->assertSame(0, Document::count());
        Storage::disk(Document::DISK)->assertMissing($path);
    }

    public function test_admin_manages_any_document(): void
    {
        $document = Document::create([
            'title' => 'Regolamento', 'path' => 'documenti/x.pdf', 'original_name' => 'x.pdf',
            'user_id' => $this->coach->id, 'for_everyone' => false,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('documents.edit', $document))->assertOk();
        $this->delete(route('documents.destroy', $document))->assertRedirect();
        $this->assertSame(0, Document::count());
    }
}
