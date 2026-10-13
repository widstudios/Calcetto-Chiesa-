<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rosa');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_import_creates_category_with_coaches_players_and_trainings(): void
    {
        $file = $this->csv("cognome;nome;anno\nDI MARTINO;Gabriele;2013\nABEBE;DEREK;2015\n");

        $args = [
            'file' => $file,
            '--categoria' => 'Under 14',
            '--stagione' => '2026/27',
            '--allenatore' => 'Piero Perilli, Simone Poggi',
            '--allenamento' => ['martedì|Pescatori|18:30|20:00', 'Sabato|Pescatori|10:00|11:30'],
        ];
        $this->artisan('rosa:importa', $args)->assertSuccessful();
        // Rilanciarlo non duplica nulla.
        $this->artisan('rosa:importa', $args)->assertSuccessful();

        $team = Team::where('name', 'Under 14')->sole();
        $this->assertSame('Under 14', $team->tournament->name);
        $this->assertSame('2026/27', $team->tournament->season);
        $this->assertSame('Piero Perilli, Simone Poggi', $team->coach_name);
        $this->assertSame(['Gabriele Di Martino', 'Derek Abebe'], $team->players->map->fullName()->all());
        $this->assertSame(2013, $team->players->first()->birth_year);
        $this->assertSame(
            ['Martedì · 18:30 - 20:00', 'Sabato · 10:00 - 11:30'],
            $team->trainings->map(fn (Training $t) => $t->weekdayName().' · '.$t->timeRange())->all(),
        );
    }

    public function test_import_rejects_bad_rows(): void
    {
        $this->artisan('rosa:importa', ['file' => $this->csv("cognome,nome,anno\nRossi,Mario,\n"), '--categoria' => 'Under 14'])
            ->assertFailed();
        $this->artisan('rosa:importa', ['file' => $this->csv("cognome,nome,anno\nRossi,Mario,2013\n"), '--categoria' => 'Under 14', '--allenamento' => ['lunedì|Campo|20:00|19:00']])
            ->assertFailed();

        $this->assertSame(0, Team::count());
    }

    public function test_coach_manages_trainings_of_own_team_only(): void
    {
        $coach = User::factory()->create();
        $own = Team::factory()->withCoachAccount($coach)->create();
        $other = Team::factory()->create();
        $slot = ['weekday' => 2, 'field' => 'Pescatori', 'starts_at' => '18:30', 'ends_at' => '20:00'];

        $this->actingAs($coach)->post(route('teams.trainings.store', $own), $slot)->assertRedirect(route('teams.show', $own));
        $this->post(route('teams.trainings.store', $other), $slot)->assertForbidden();
        $this->post(route('teams.trainings.store', $own), [...$slot, 'ends_at' => '18:00'])->assertSessionHasErrors('ends_at');

        $this->get(route('teams.show', $own))->assertSee('Martedì · 18:30 - 20:00')->assertSee('Campo: Pescatori');

        $training = $own->trainings()->sole();
        $this->delete(route('teams.trainings.destroy', [$own, $training]))->assertRedirect();
        $this->assertSame(0, $own->trainings()->count());
    }
}
