<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
