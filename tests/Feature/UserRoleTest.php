<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Capo Torneo']);
        User::factory()->create(['name' => 'Piero Perilli']);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()->assertSee('Capo Torneo')->assertSee('Piero Perilli')->assertSee('Allenatore');
    }

    public function test_coach_cannot_manage_users(): void
    {
        $coach = User::factory()->create();

        $this->actingAs($coach)->get(route('users.index'))->assertForbidden();
        $this->actingAs($coach)->put(route('users.update', $coach), ['name' => $coach->name, 'email' => $coach->email, 'role' => 'admin'])->assertForbidden();
        $this->assertSame(Role::Allenatore, $coach->fresh()->role);
    }

    public function test_admin_promotes_coach_to_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->create();

        $this->actingAs($admin)->put(route('users.update', $coach), ['name' => $coach->name, 'email' => $coach->email, 'role' => 'admin'])
            ->assertRedirect(route('users.index'));

        $this->assertSame(Role::Admin, $coach->fresh()->role);
    }

    public function test_admin_creates_new_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nuovo Admin', 'email' => 'nuovo@example.com', 'password' => 'Password123', 'role' => 'admin',
        ])->assertRedirect(route('users.index'));

        $this->assertSame(Role::Admin, User::where('email', 'nuovo@example.com')->first()->role);
    }

    public function test_admin_can_demote_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('users.update', $other), ['name' => $other->name, 'email' => $other->email, 'role' => 'allenatore']);

        $this->assertSame(Role::Allenatore, $other->fresh()->role);
    }

    public function test_admin_cannot_demote_or_delete_self(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('users.edit', $admin))
            ->put(route('users.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'allenatore'])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_password_change_keeps_role(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->create();

        $this->actingAs($admin)->put(route('users.update', $coach), [
            'name' => 'Nome Nuovo', 'email' => $coach->email, 'password' => '', 'role' => 'allenatore',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nome Nuovo', $coach->fresh()->name);
        $this->assertSame($coach->password, $coach->fresh()->password);
    }

    public function test_last_admin_cannot_be_demoted_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        // Situazione limite: chi agisce è appena stato declassato da un altro admin.
        $admin->update(['role' => Role::Allenatore]);
        $admin->role = Role::Admin;

        $this->actingAs($admin)->put(route('users.update', $other), ['name' => $other->name, 'email' => $other->email, 'role' => 'allenatore'])
            ->assertSessionHasErrors('role');
        $this->actingAs($admin)->delete(route('users.destroy', $other))->assertSessionHasErrors('user');

        $this->assertSame(Role::Admin, $other->fresh()->role);
    }
}
