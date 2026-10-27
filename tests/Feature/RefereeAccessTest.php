<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Models\Game;
use App\Models\Referee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefereeAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function refereeWithAccount(): Referee
    {
        $referee = Referee::factory()->create();
        $referee->user()->associate(User::factory()->create(['role' => Role::Arbitro, 'name' => $referee->fullName()]))->save();

        return $referee;
    }

    public function test_admin_gives_referee_access_to_the_app(): void
    {
        $this->actingAs($this->admin)->post(route('referees.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Fischietto',
            'email' => 'mario@arbitri.it',
            'password' => 'fischio123',
        ])->assertRedirect(route('referees.index'));

        $referee = Referee::firstWhere('last_name', 'Fischietto');
        $this->assertSame(Role::Arbitro, $referee->user->role);
        $this->assertSame('Mario Fischietto', $referee->user->name);
        $this->assertTrue(Hash::check('fischio123', $referee->user->password));

        $this->get(route('referees.index'))->assertSee("Accede all'app", false);
    }

    public function test_email_without_password_is_rejected_for_a_new_account(): void
    {
        $this->actingAs($this->admin)->post(route('referees.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Fischietto',
            'email' => 'mario@arbitri.it',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('referees', 0);
    }

    public function test_referee_without_email_has_no_account(): void
    {
        $this->actingAs($this->admin)->post(route('referees.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Fischietto',
        ])->assertRedirect(route('referees.index'));

        $this->assertNull(Referee::first()->user_id);
        $this->assertSame(1, User::count());
    }

    public function test_clearing_email_removes_access_and_deleting_referee_removes_account(): void
    {
        $referee = $this->refereeWithAccount();
        $userId = $referee->user_id;

        $this->actingAs($this->admin)->put(route('referees.update', $referee), [
            'first_name' => $referee->first_name,
            'last_name' => $referee->last_name,
            'email' => $referee->user->email,
        ])->assertRedirect(route('referees.index'));
        $this->assertNotNull(User::find($userId), 'Senza nuova password l\'account resta');

        $this->put(route('referees.update', $referee), [
            'first_name' => $referee->first_name,
            'last_name' => $referee->last_name,
            'email' => '',
        ]);
        $this->assertNull(User::find($userId));
        $this->assertNull($referee->fresh()->user_id);

        $other = $this->refereeWithAccount();
        $otherUserId = $other->user_id;
        $this->delete(route('referees.destroy', $other));
        $this->assertNull(User::find($otherUserId));
    }

    public function test_referee_sees_only_own_games(): void
    {
        $referee = $this->refereeWithAccount();
        $mine = Game::factory()->create(['referee_id' => $referee->id, 'kickoff_at' => now()->addDay()]);
        $played = Game::factory()->create(['referee_id' => $referee->id, 'status' => GameStatus::Giocata, 'home_score' => 2, 'away_score' => 1, 'kickoff_at' => now()->subWeek()]);
        $notMine = Game::factory()->create(['referee_id' => Referee::factory()->create()->id]);

        $this->actingAs($referee->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Le mie partite')
            ->assertSee($mine->homeTeam->name)
            ->assertSee($played->homeTeam->name)
            ->assertSee('Già arbitrate')
            ->assertDontSee($notMine->homeTeam->name)
            ->assertDontSee(route('games.edit', $mine))
            ->assertDontSee(route('guide'));
    }

    public function test_referee_cannot_open_or_change_anything_else(): void
    {
        $referee = $this->refereeWithAccount();
        $game = Game::factory()->create(['referee_id' => $referee->id]);
        $team = Team::factory()->create();

        $this->actingAs($referee->user);

        foreach ([
            route('teams.index'), route('teams.show', $team), route('calendar.index'), route('attendances.index'),
            route('lineups.index'), route('documents.index'), route('drills.index'), route('guide'),
            route('games.edit', $game), route('games.sheet.edit', $game), route('referees.index'), route('users.index'),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }

        $this->put(route('games.update', $game), ['home_score' => 5])->assertForbidden();
        $this->post(route('calendar.events.store'), [])->assertForbidden();

        // La password la può cambiare.
        $this->get(route('password.edit'))->assertOk();
    }

    public function test_users_page_sends_referee_accounts_to_referee_form(): void
    {
        $referee = $this->refereeWithAccount();

        $this->actingAs($this->admin)->get(route('users.index'))
            ->assertSee(route('referees.edit', $referee));
        $this->get(route('users.edit', $referee->user))->assertRedirect(route('referees.edit', $referee));

        // Il ruolo arbitro non si assegna da Utenti: va collegato a una scheda arbitro.
        $this->post(route('users.store'), [
            'name' => 'Senza scheda',
            'email' => 'x@example.com',
            'password' => 'password123',
            'role' => Role::Arbitro->value,
        ])->assertSessionHasErrors('role');
    }

    public function test_coach_still_reaches_own_pages(): void
    {
        $this->actingAs(User::factory()->create())->get(route('calendar.index'))->assertOk();
    }
}
