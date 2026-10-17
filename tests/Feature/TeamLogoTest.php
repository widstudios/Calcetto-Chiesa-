<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamLogoTest extends TestCase
{
    use RefreshDatabase;

    private function png(string $name = 'stemma.png'): UploadedFile
    {
        // PNG vero di 1x1 pixel: non serve l'estensione GD.
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
    }

    public function test_admin_sets_logo_and_shirt_colors(): void
    {
        Storage::fake('uploads');
        $admin = User::factory()->admin()->create();
        $tournament = Tournament::factory()->create();

        $this->actingAs($admin)->post(route('teams.store'), [
            'tournament_id' => $tournament->id,
            'name' => 'San Nicola',
            'logo' => $this->png(),
            'shirt_color' => '#dc2626',
            'shirt_color_secondary' => '#ffffff',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $team = Team::firstWhere('name', 'San Nicola');
        Storage::disk('uploads')->assertExists($team->logo);
        $this->assertSame('Rosso e Bianco', $team->shirtColorsLabel());

        // Lo stemma compare nella scheda della squadra, nelle partite e nel sito pubblico.
        $other = Team::factory()->for($tournament)->create(['shirt_color' => '#1d4ed8']);
        Game::factory()->for($tournament)->played(2, 1)->create(['home_team_id' => $team->id, 'away_team_id' => $other->id]);
        $this->get(route('teams.show', $team))->assertSee($team->logoUrl(), false)->assertSee('Rosso e Bianco');
        $this->get(route('games.index'))->assertSee($team->logoUrl(), false)->assertSee('fill="#1d4ed8"', false);
        $this->get('/')->assertSee($team->logoUrl(), false);

        // Un logo nuovo prende il posto del vecchio; poi si può togliere.
        $old = $team->logo;
        $this->put(route('teams.update', $team), ['tournament_id' => $tournament->id, 'name' => 'San Nicola', 'logo' => $this->png('nuovo.png'), 'shirt_color' => '', 'shirt_color_secondary' => '#ffffff'])
            ->assertSessionHasNoErrors();
        Storage::disk('uploads')->assertMissing($old);
        $team->refresh();
        Storage::disk('uploads')->assertExists($team->logo);
        $this->assertSame('Bianco', $team->shirtColorsLabel());

        $this->put(route('teams.update', $team), ['tournament_id' => $tournament->id, 'name' => 'San Nicola', 'remove_logo' => '1']);
        Storage::disk('uploads')->assertMissing($team->logo);
        $this->assertNull($team->fresh()->logo);
    }

    public function test_logo_and_colors_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        $this->actingAs($admin)->put(route('teams.update', $team), [
            'tournament_id' => $team->tournament_id,
            'name' => $team->name,
            'logo' => UploadedFile::fake()->createWithContent('stemma.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'shirt_color' => 'red',
        ])->assertSessionHasErrors(['logo', 'shirt_color']);
    }

    public function test_deleting_team_deletes_its_logo(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('squadra-1.png', 'x');
        $team = Team::factory()->create(['logo' => 'squadra-1.png']);

        $this->actingAs(User::factory()->admin()->create())->delete(route('teams.destroy', $team));

        Storage::disk('uploads')->assertMissing('squadra-1.png');
    }

    public function test_deleting_tournament_deletes_its_team_logos(): void
    {
        Storage::fake('uploads');
        Storage::disk('uploads')->put('squadra-1.png', 'x');
        $team = Team::factory()->create(['logo' => 'squadra-1.png']);

        $this->actingAs(User::factory()->admin()->create())->delete(route('tournaments.destroy', $team->tournament));

        Storage::disk('uploads')->assertMissing('squadra-1.png');
    }

    public function test_san_nicola_gets_its_logo_from_migration(): void
    {
        Storage::fake('uploads');
        $team = Team::factory()->create(['name' => 'C.S.P. San Nicola']);
        $other = Team::factory()->create(['name' => 'Oratorio United']);

        $migration = require database_path('migrations/2026_10_17_160001_add_san_nicola_logo.php');
        $migration->up();

        $team->refresh();
        Storage::disk('uploads')->assertExists($team->logo);
        $this->assertSame(['#dc2626', '#ffffff'], $team->shirtColors());
        $this->assertNull($other->fresh()->logo);
    }
}
