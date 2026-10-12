<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_change_password(): void
    {
        $this->get('/password')->assertRedirect('/login');
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/password')->assertOk()->assertSee('Cambia password');

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'nuova-password',
            'password_confirmation' => 'nuova-password',
        ])->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('nuova-password', $user->fresh()->password));
    }

    public function test_changing_password_logs_out_other_devices(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['remember_token' => 'vecchio-token']);
        DB::table('sessions')->insert(['id' => 'altro-dispositivo', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'nuova-password',
            'password_confirmation' => 'nuova-password',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'altro-dispositivo']);
        $this->assertNotSame('vecchio-token', $user->fresh()->remember_token);
    }

    public function test_password_attempts_are_throttled(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            $this->actingAs($user)->put('/password', ['current_password' => 'sbagliata'])->assertSessionHasErrors();
        }

        $this->actingAs($user)->put('/password', ['current_password' => 'sbagliata'])->assertTooManyRequests();
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'sbagliata',
            'password' => 'nuova-password',
            'password_confirmation' => 'nuova-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'nuova-password',
            'password_confirmation' => 'altra-password',
        ])->assertSessionHasErrors('password');
    }
}
