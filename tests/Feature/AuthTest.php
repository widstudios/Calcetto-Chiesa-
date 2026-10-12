<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Area riservata');
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Accedi');
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'sbagliata'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_role_middleware_restricts_access(): void
    {
        Route::middleware(['web', 'auth', 'role:admin'])->get('/_solo-admin', fn () => 'ok');

        $this->actingAs(User::factory()->create())->get('/_solo-admin')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/_solo-admin')->assertOk();
    }
}
