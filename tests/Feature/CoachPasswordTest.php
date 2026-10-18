<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CoachPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_password_to_coaches_except_excluded(): void
    {
        $piero = User::factory()->create(['name' => 'Piero Perilli']);
        $mirko = User::factory()->create(['name' => 'Mirko Dei Santi']);
        $ruggero = User::factory()->create(['name' => 'Ruggero Genna']);
        $admin = User::factory()->admin()->create(['name' => 'Admin']);

        $this->artisan('mister:password', ['password' => 'Segreta2026', '--escludi' => ['mirko', 'Ruggero']])
            ->expectsOutput('1 mister aggiornati: Piero Perilli.')
            ->expectsOutput('Esclusi: Mirko Dei Santi, Ruggero Genna.')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('Segreta2026', $piero->fresh()->password));
        $this->assertFalse(Hash::check('Segreta2026', $mirko->fresh()->password));
        $this->assertFalse(Hash::check('Segreta2026', $ruggero->fresh()->password));
        $this->assertFalse(Hash::check('Segreta2026', $admin->fresh()->password));
    }

    public function test_rejects_short_password(): void
    {
        $coach = User::factory()->create();
        $before = $coach->password;

        $this->artisan('mister:password', ['password' => 'corta'])->assertFailed();
        $this->assertSame($before, $coach->fresh()->password);
    }
}
