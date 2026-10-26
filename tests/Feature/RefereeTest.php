<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefereeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_pages_render(): void
    {
        $referee = Referee::factory()->create();
        Game::factory()->create(['referee_id' => $referee->id]);

        $this->actingAs($this->admin);

        foreach ([route('referees.index'), route('referees.create'), route('referees.edit', $referee)] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_adds_referee_with_games(): void
    {
        $game = Game::factory()->create();
        $other = Game::factory()->create();

        $this->actingAs($this->admin)->post(route('referees.store'), [
            'first_name' => 'Mario',
            'last_name' => 'Fischietto',
            'phone' => '+39 333 000 1111',
            'games' => [$game->id],
        ])->assertRedirect(route('referees.index'));

        $referee = Referee::firstWhere('last_name', 'Fischietto');
        $this->assertSame('+39 333 000 1111', $referee->phone);
        $this->assertTrue($game->fresh()->referee->is($referee));
        $this->assertNull($other->fresh()->referee_id);

        $this->get(route('referees.index'))
            ->assertSee('Mario Fischietto')
            ->assertSee('tel:+393330001111', false)
            ->assertSee($game->homeTeam->name);
    }

    public function test_updating_games_unassigns_the_ones_left_out(): void
    {
        $referee = Referee::factory()->create();
        $kept = Game::factory()->create(['referee_id' => $referee->id]);
        $dropped = Game::factory()->create(['referee_id' => $referee->id]);

        $this->actingAs($this->admin)->put(route('referees.update', $referee), [
            'first_name' => $referee->first_name,
            'last_name' => $referee->last_name,
            'phone' => '',
            'games' => [$kept->id],
        ])->assertRedirect(route('referees.index'));

        $this->assertSame($referee->id, $kept->fresh()->referee_id);
        $this->assertNull($dropped->fresh()->referee_id);
        $this->assertNull($referee->fresh()->phone);
    }

    public function test_invalid_game_saves_nothing(): void
    {
        $referee = Referee::factory()->create(['phone' => '333 000 2222']);

        $this->actingAs($this->admin)->post(route('referees.store'), [
            'first_name' => 'Anna', 'last_name' => 'Rigore', 'games' => [999],
        ])->assertSessionHasErrors('games.0');
        $this->put(route('referees.update', $referee), [
            'first_name' => $referee->first_name, 'last_name' => $referee->last_name, 'phone' => '', 'games' => [999],
        ])->assertSessionHasErrors('games.0');

        $this->assertDatabaseMissing('referees', ['last_name' => 'Rigore']);
        $this->assertSame('333 000 2222', $referee->fresh()->phone);
    }

    public function test_empty_game_choice_survives_validation_error(): void
    {
        $referee = Referee::factory()->create();
        $game = Game::factory()->create(['referee_id' => $referee->id]);

        $this->actingAs($this->admin)->from(route('referees.edit', $referee))->put(route('referees.update', $referee), [
            'first_name' => $referee->first_name, 'last_name' => $referee->last_name, 'phone' => 'abc', 'games_sent' => '1',
        ])->assertSessionHasErrors('phone');

        $this->get(route('referees.edit', $referee))
            ->assertSee('value="'.$game->id.'"', false)
            ->assertDontSee('value="'.$game->id.'" checked', false);
    }

    public function test_form_explains_when_there_are_no_games_yet(): void
    {
        $this->actingAs($this->admin)->get(route('referees.create'))->assertSee('Non ci sono ancora partite da giocare');

        Game::factory()->create(['referee_id' => Referee::factory()->create()->id]);
        $this->get(route('referees.create'))->assertSee('tutte quelle da giocare hanno già un arbitro');
    }

    public function test_form_offers_only_free_games_and_own_games(): void
    {
        $referee = Referee::factory()->create();
        $mine = Game::factory()->create(['referee_id' => $referee->id]);
        $free = Game::factory()->create();
        $taken = Game::factory()->create(['referee_id' => Referee::factory()->create()->id]);
        $played = Game::factory()->played(1, 0)->create();

        $this->actingAs($this->admin)->get(route('referees.edit', $referee))
            ->assertSee('value="'.$mine->id.'" checked', false)
            ->assertSee('value="'.$free->id.'"', false)
            ->assertDontSee('value="'.$taken->id.'"', false)
            ->assertDontSee('value="'.$played->id.'"', false);
    }

    public function test_referee_is_chosen_from_game_form(): void
    {
        $referee = Referee::factory()->create(['first_name' => 'Luca', 'last_name' => 'Cartellino']);
        $game = Game::factory()->create();

        $this->actingAs($this->admin)->put(route('games.update', $game), [
            'home_team_id' => $game->home_team_id,
            'away_team_id' => $game->away_team_id,
            'referee_id' => $referee->id,
            'status' => GameStatus::Programmata->value,
        ])->assertRedirect();

        $this->assertSame($referee->id, $game->fresh()->referee_id);
        $this->get(route('games.index', ['tournament' => $game->tournament_id]))->assertSee('Arbitro: Luca Cartellino');
    }

    public function test_deleting_referee_keeps_games(): void
    {
        $referee = Referee::factory()->create();
        $game = Game::factory()->create(['referee_id' => $referee->id]);

        $this->actingAs($this->admin)->delete(route('referees.destroy', $referee))->assertRedirect(route('referees.index'));

        $this->assertModelMissing($referee);
        $this->assertNull($game->fresh()->referee_id);
    }

    public function test_phone_never_reaches_public_site(): void
    {
        $referee = Referee::factory()->create(['first_name' => 'Paolo', 'last_name' => 'Fuorigioco', 'phone' => '333 000 9999']);
        Game::factory()->create(['referee_id' => $referee->id]);

        $this->get(route('home'))->assertOk()->assertDontSee('333 000 9999')->assertDontSee('Fuorigioco');
    }

    public function test_coach_cannot_see_referees(): void
    {
        $this->actingAs(User::factory()->create())->get(route('referees.index'))->assertForbidden();
    }
}
