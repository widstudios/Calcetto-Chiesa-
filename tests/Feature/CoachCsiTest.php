<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachCsiTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'mister');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_import_records_registered_and_pending_coaches(): void
    {
        $existing = User::factory()->create(['name' => 'Simone Poggi', 'email' => 'simone@example.com']);
        $file = $this->csv("nome;email;tesserato_il\nSimone Poggi;Simone@Example.com;28/09/2026\nPiero Perilli;piero@example.com;\n");

        $this->artisan('mister:importa', ['file' => $file])
            ->expectsOutput('1 mister aggiunti, 1 aggiornati. Tesserati CSI: 1, da tesserare: 1.')
            ->assertSuccessful();

        $this->assertSame('2026-09-28', $existing->fresh()->csi_registered_on->format('Y-m-d'));
        $piero = User::firstWhere('email', 'piero@example.com');
        $this->assertSame(Role::Allenatore, $piero->role);
        $this->assertFalse($piero->isCsiRegistered());

        // Rilanciato con una cella vuota non cancella la data già salvata.
        $this->artisan('mister:importa', ['file' => $this->csv("nome;email;tesserato_il\nSimone Poggi;simone@example.com;\n")])->assertSuccessful();
        $this->assertTrue($existing->fresh()->isCsiRegistered());
        $this->assertSame(2, User::count());
    }

    public function test_import_rejects_bad_rows(): void
    {
        $this->artisan('mister:importa', ['file' => $this->csv("nome;email;tesserato_il\nPiero;non-una-email;\n")])->assertFailed();
        $this->artisan('mister:importa', ['file' => $this->csv("nome;email;tesserato_il\nPiero;piero@example.com;31/02/2026\n")])->assertFailed();
        $this->assertSame(0, User::count());
    }

    public function test_admin_sees_csi_status_and_teams_named_by_surname(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Simone Poggi', 'csi_registered_on' => '2026-09-28']);
        User::factory()->create(['name' => 'Piero Perilli']);
        Team::factory()->create(['name' => 'Under 14', 'coach_name' => 'Piero Perilli, Simone Poggi']);

        $this->actingAs($admin)->get(route('coaches.index'))
            ->assertOk()
            ->assertSee('Tesserato CSI dal 28/09/2026')
            ->assertSee('Da tesserare')
            ->assertSee('Under 14');
    }

    public function test_admin_adds_coach_without_password_and_sets_csi_date(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('coaches.store'), ['name' => 'Marco Malapena', 'email' => 'marco@example.com'])
            ->assertRedirect(route('coaches.index'));

        $coach = User::firstWhere('email', 'marco@example.com');
        $this->assertFalse($coach->isCsiRegistered());

        $this->put(route('coaches.update', $coach), ['name' => 'Marco Malapena', 'email' => 'marco@example.com', 'csi_registered_on' => '2026-10-10'])
            ->assertRedirect(route('coaches.index'));
        $this->assertSame('2026-10-10', $coach->fresh()->csi_registered_on->format('Y-m-d'));
    }
}
