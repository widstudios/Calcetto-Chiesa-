<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Support\CsiImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CsiImportTest extends TestCase
{
    use RefreshDatabase;

    private function game(string $code, string $home, string $away, ?string $kickoff = null, ?array $score = null, string $category = 'Under 10', string $group = 'B'): array
    {
        return [
            'code' => $code, 'category' => $category, 'group' => $group, 'home' => $home, 'away' => $away,
            'kickoff' => $kickoff ? Carbon::parse($kickoff) : null, 'postponed' => null,
            'home_score' => $score[0] ?? null, 'away_score' => $score[1] ?? null,
        ];
    }

    public function test_without_club_name_nothing_is_imported(): void
    {
        $result = (new CsiImport)->apply([$this->game('GXC10B1', 'S. Atanasio', 'Bravetta')]);

        $this->assertSame(0, $result['saved']);
        $this->assertStringContainsString('nome della società', $result['notes'][0]);
        $this->assertSame(0, Game::count());
    }

    public function test_games_go_to_our_team_and_opponents_are_created(): void
    {
        Setting::put('csi_club_name', 'S. Atanasio');
        $tournament = Tournament::factory()->create(['name' => 'Under 10']);
        $ours = Team::factory()->for($tournament)->create(['name' => 'Pulcini']);
        Player::factory()->count(3)->for($ours)->create();
        Team::factory()->for($tournament)->create(['name' => 'Under 10']);

        $games = [
            $this->game('GXC10B1', 'S.Atanasio', 'Bravetta', '2026-10-12 16:30', [4, 8]),
            $this->game('GXC10B2', 'Regina Pacis', 'Bravetta', '2026-10-19 10:00'),
            // Altro girone, squadre sconosciute: non ci riguarda.
            $this->game('GXC10A1', 'Santa Rita', 'San Gelasio', group: 'A'),
            // Categoria che nel gestionale non c'è.
            $this->game('GXC12A1', 'S. Atanasio', 'Escrivà', category: 'Under 12', group: 'A'),
        ];

        $result = (new CsiImport)->apply($games);

        $this->assertSame(['found' => 4, 'saved' => 2], array_slice($result, 0, 2));
        $this->assertStringContainsString('Under 12', $result['notes'][0]);

        $played = Game::where('csi_code', 'GXC10B1')->first();
        $this->assertTrue($played->homeTeam->is($ours));
        $this->assertSame('Bravetta', $played->awayTeam->name);
        $this->assertSame([4, 8], [$played->home_score, $played->away_score]);
        $this->assertSame(GameStatus::Giocata, $played->status);
        $this->assertSame(GameStatus::Programmata, Game::where('csi_code', 'GXC10B2')->first()->status);
        $this->assertFalse(Team::where('name', 'Santa Rita')->exists());

        // Rileggere lo stesso comunicato non cambia niente.
        $this->assertSame(0, (new CsiImport)->apply($games)['saved']);
        $this->assertSame(2, Game::count());
    }

    public function test_a_game_entered_by_hand_is_updated_instead_of_duplicated(): void
    {
        Setting::put('csi_club_name', 'S. Atanasio');
        $tournament = Tournament::factory()->create(['name' => 'Under 10']);
        $ours = Team::factory()->for($tournament)->create(['name' => 'Under 10']);
        $rival = Team::factory()->for($tournament)->create(['name' => 'Josemaría Escrivà']);
        $game = Game::factory()->create([
            'tournament_id' => $tournament->id, 'home_team_id' => $rival->id, 'away_team_id' => $ours->id,
            'kickoff_at' => '2026-10-26 17:15', 'status' => GameStatus::Programmata, 'home_score' => null, 'away_score' => null,
        ]);

        // Il CSI sbaglia una lettera e scrive solo la data: l'ora inserita a mano resta.
        (new CsiImport)->apply([$this->game('GXC10B5', 'Josemaría Escrvà', 'S. Atanasio', '2026-10-26', [2, 2])]);

        $this->assertSame(1, Game::count());
        $game->refresh();
        $this->assertSame('GXC10B5', $game->csi_code);
        $this->assertSame('2026-10-26 17:15', $game->kickoff_at->format('Y-m-d H:i'));
        $this->assertSame([2, 2], [$game->home_score, $game->away_score]);
    }

    public function test_admin_saves_club_name_from_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/comunicati-csi/societa', ['csi_club_name' => 'S. Atanasio'])
            ->assertRedirect('/comunicati-csi');
        $this->assertSame('S. Atanasio', Setting::get('csi_club_name'));

        $this->actingAs($admin)->post('/comunicati-csi/rileggi')
            ->assertRedirect('/comunicati-csi')->assertSessionHas('status', '0 partite aggiornate dai comunicati.');
        $this->actingAs($admin)->get('/comunicati-csi')->assertOk()->assertSee('S. Atanasio');
    }
}
