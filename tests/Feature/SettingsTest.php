<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function png(string $name = 'logo.png'): UploadedFile
    {
        // PNG vero di 1x1 pixel: non serve l'estensione GD.
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    }

    public function test_only_admin_can_open_settings(): void
    {
        $this->get('/impostazioni')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/impostazioni')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/impostazioni')->assertOk()->assertSee('Logo');
    }

    public function test_admin_uploads_logo_and_renames_site(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/impostazioni', ['site_name' => 'Torneo Oratorio', 'logo' => $this->png()])
            ->assertRedirect('/impostazioni')->assertSessionHasNoErrors();

        $path = Setting::get('logo');
        $this->assertNotNull($path);
        Storage::disk('uploads')->assertExists($path);

        // Il logo compare nel gestionale e nel sito pubblico, anche come icona della scheda.
        $url = Setting::logoUrl();
        $this->assertStringEndsWith($path, $url);
        $this->actingAs($admin)->get('/dashboard')->assertSee('<img src="'.$url.'"', false)->assertSee('Torneo Oratorio');
        $this->get('/')->assertSee('<link rel="icon" href="'.$url.'">', false)->assertSee('Torneo Oratorio');

        // Un logo nuovo prende il posto del vecchio, che viene cancellato.
        $this->actingAs($admin)->put('/impostazioni', ['site_name' => 'Torneo Oratorio', 'logo' => $this->png('nuovo.png')]);
        Storage::disk('uploads')->assertMissing($path);
        Storage::disk('uploads')->assertExists(Setting::get('logo'));
    }

    public function test_admin_can_remove_logo_and_reset_name(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put('/impostazioni', ['site_name' => 'Torneo Oratorio', 'logo' => $this->png()]);
        $path = Setting::get('logo');

        $this->actingAs($admin)->put('/impostazioni', ['site_name' => '', 'remove_logo' => '1'])->assertSessionHasNoErrors();

        $this->assertNull(Setting::get('logo'));
        $this->assertSame(config('app.name'), Setting::siteName());
        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_logo_must_be_a_raster_image(): void
    {
        Storage::fake('uploads');
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs(User::factory()->admin()->create())->put('/impostazioni', ['logo' => $svg])
            ->assertSessionHasErrors('logo');
        $this->assertNull(Setting::get('logo'));
    }
}
