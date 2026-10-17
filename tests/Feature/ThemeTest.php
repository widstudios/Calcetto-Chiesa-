<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Svuota i valori di Setting rimasti in memoria dal test precedente.
        Setting::put('color_header_bg', null);
    }

    public function test_default_colors_add_no_style(): void
    {
        $this->assertNull(Theme::css());
        $this->get('/')->assertOk()->assertSee('<meta name="theme-color" content="#052e16">', false)->assertDontSee('--header-bg:', false);
    }

    public function test_san_nicola_preset_changes_colors_everywhere_without_touching_other_settings(): void
    {
        $admin = User::factory()->admin()->create();
        Setting::put('site_name', 'Oratorio Cup');

        $this->actingAs($admin)->put('/impostazioni', ['preset' => 'san-nicola'])
            ->assertRedirect('/impostazioni')->assertSessionHasNoErrors();

        $this->assertSame('#ffffff', Theme::color('header_bg'));
        $this->assertSame('#d71920', Theme::color('header_text'));
        $this->assertSame('Oratorio Cup', Setting::siteName());

        $this->get('/')->assertSee('--header-bg:#ffffff', false)->assertSee('--header-text:#d71920', false)->assertSee('--heading:#d71920', false);
        $this->get('/impostazioni')->assertSee('--primary:#d71920', false);
        $this->post('/logout');
        $this->get('/login')->assertSee('--primary:#d71920', false);

        // Si torna alla grafica verde: nessun colore salvato.
        $this->actingAs($admin)->put('/impostazioni', ['preset' => 'verde'])->assertSessionHasNoErrors();
        $this->assertNull(Setting::get('color_header_bg'));
        $this->assertNull(Theme::css());
    }

    public function test_custom_colors_are_saved_with_the_form(): void
    {
        $colors = ['header_bg' => '#FFFFFF', 'header_text' => '#1e3a8a', 'primary' => '#1d4ed8', 'heading' => '#0f172a', 'accent' => '#f59e0b'];

        $this->actingAs(User::factory()->admin()->create())->put('/impostazioni', ['colors' => $colors])
            ->assertRedirect('/impostazioni')->assertSessionHasNoErrors();

        $this->assertSame('#ffffff', Theme::color('header_bg'));
        $this->assertSame('#1d4ed8', Theme::color('primary'));
        // Uguale al predefinito: non salvato.
        $this->assertNull(Setting::get('color_heading'));
    }

    public function test_unreadable_colors_are_refused(): void
    {
        $admin = User::factory()->admin()->create();
        $colors = ['header_bg' => '#ffffff', 'header_text' => '#fde68a', 'primary' => '#86efac', 'heading' => '#0f172a', 'accent' => '#f59e0b'];

        $this->actingAs($admin)->from('/impostazioni')->put('/impostazioni', ['site_name' => 'Nuovo nome', 'colors' => $colors])
            ->assertRedirect('/impostazioni')->assertSessionHasErrors(['colors.header_text', 'colors.primary']);

        $this->assertNull(Setting::get('color_header_bg'));
        $this->assertNull(Setting::get('site_name'));

        $this->actingAs($admin)->put('/impostazioni', ['colors' => ['primary' => 'red']])->assertSessionHasErrors('colors.primary');
    }

    public function test_contrast_ratio(): void
    {
        $this->assertEqualsWithDelta(21, Theme::contrast('#000000', '#ffffff'), .01);
        $this->assertGreaterThanOrEqual(4.5, Theme::contrast('#d71920', '#ffffff'));
        $this->assertSame([], Theme::contrastErrors(Theme::PRESETS['san-nicola']['colors']));
        $this->assertSame([], Theme::contrastErrors(Theme::PRESETS['verde']['colors']));

        // Bianco su #767676 passa (4,5:1) ma non sul lato più chiaro della sfumatura.
        $grey = ['header_bg' => '#767676', 'header_text' => '#ffffff'] + Theme::PRESETS['verde']['colors'];
        $this->assertArrayHasKey('header_text', Theme::contrastErrors($grey));
    }
}
