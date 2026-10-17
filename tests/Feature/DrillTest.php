<?php

namespace Tests\Feature;

use App\Enums\DrillCategory;
use App\Models\Drill;
use App\Models\Player;
use App\Models\Team;
use App\Models\TrainingDrill;
use App\Models\User;
use Database\Seeders\DrillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DrillTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_loads_catalogue_once_with_valid_videos_and_diagrams(): void
    {
        $this->seed(DrillSeeder::class);
        $count = Drill::count();
        $this->assertSame(count(DrillSeeder::drills()), $count);

        // Rilanciato non duplica e non sovrascrive le modifiche dell'admin.
        Drill::where('slug', 'conduzione-slalom')->update(['name' => 'Slalom del mister']);
        $this->seed(DrillSeeder::class);
        $this->assertSame($count, Drill::count());
        $this->assertSame('Slalom del mister', Drill::where('slug', 'conduzione-slalom')->value('name'));

        foreach (Drill::all() as $drill) {
            $this->assertNotNull($drill->youtubeId(), $drill->slug);
            $this->assertArrayHasKey($drill->diagram, Drill::DIAGRAMS, $drill->slug);
            $this->assertLessThanOrEqual($drill->age_max, $drill->age_min);
        }
        // Ogni tipo di allenamento ha almeno un esercizio.
        $this->assertCount(count(DrillCategory::cases()), Drill::grouped());
    }

    public function test_coach_picks_category_and_drill_and_sees_video_and_diagram(): void
    {
        $this->seed(DrillSeeder::class);
        $coach = User::factory()->create();

        $this->actingAs($coach)->get(route('drills.index'))->assertOk()
            ->assertSee('Tipo di allenamento')->assertSee('Cambio di direzione')->assertDontSee('Nuovo esercizio');

        $this->get(route('drills.index', ['categoria' => 'cambio_direzione']))->assertOk()
            ->assertSee('Cambi di direzione tra i coni (zig-zag)')->assertDontSee('Conduzione a slalom');

        $drill = Drill::where('slug', 'cambi-direzione-zigzag')->first();
        $this->get(route('drills.index', ['esercizio' => $drill->id]))->assertOk()
            ->assertSee('Cosa devono fare')
            ->assertSee('https://www.youtube-nocookie.com/embed/Ts6AKhsz-Z8', false)
            ->assertSee('class="pitch"', false)
            ->assertSee('<option value="cambio_direzione" selected', false);
    }

    public function test_every_diagram_renders(): void
    {
        foreach (array_keys(Drill::DIAGRAMS) as $type) {
            $this->assertStringContainsString('<svg class="pitch"', view('drills.diagram', ['type' => $type])->render(), $type);
        }
    }

    public function test_only_admin_manages_drills_and_video_must_be_youtube(): void
    {
        $coach = User::factory()->create();
        $this->actingAs($coach)->get(route('drills.create'))->assertForbidden();
        $this->post(route('drills.store'), [])->assertForbidden();

        $admin = User::factory()->admin()->create();
        $valid = [
            'name' => 'Torello',
            'category' => 'passaggio',
            'age_min' => 9,
            'age_max' => 14,
            'description' => 'Passaggi in cerchio con uno al centro che prova a rubare palla.',
            'steps' => "Passaggi a due tocchi\n\nChi sbaglia va al centro",
            'diagram' => 'quadrato',
            'video_url' => 'https://youtu.be/uyO5n6FCFsQ?si=abc',
        ];

        $this->actingAs($admin)->post(route('drills.store'), [...$valid, 'video_url' => 'https://vimeo.com/123'])
            ->assertSessionHasErrors('video_url');
        $this->post(route('drills.store'), [...$valid, 'age_min' => 15])->assertSessionHasErrors('age_max');

        $this->post(route('drills.store'), $valid)->assertRedirect();
        $drill = Drill::firstWhere('name', 'Torello');
        $this->assertSame('uyO5n6FCFsQ', $drill->youtubeId());
        $this->assertCount(2, $drill->stepList());

        $this->get(route('drills.index', ['esercizio' => $drill->id]))->assertOk()
            ->assertSee('youtube-nocookie.com/embed/uyO5n6FCFsQ', false)->assertSee('Modifica');

        $this->put(route('drills.update', $drill), [...$valid, 'name' => 'Torello 4 contro 1', 'video_url' => ''])->assertRedirect();
        $this->assertNull($drill->fresh()->embedUrl());

        $this->delete(route('drills.destroy', $drill))->assertRedirect(route('drills.index', ['categoria' => 'passaggio']));
        $this->assertSame(0, Drill::count());
    }

    public function test_coach_records_drills_done_with_roll_call(): void
    {
        Carbon::setTestNow('2026-10-16 18:00');
        $this->seed(DrillSeeder::class);
        $coach = User::factory()->create();
        $team = Team::factory()->create();
        $team->coaches()->attach($coach);
        $team->trainings()->create(['weekday' => 3, 'field' => 'Oratorio', 'starts_at' => '18:00', 'ends_at' => '19:30']);
        $player = Player::factory()->for($team)->create();
        $zigzag = Drill::firstWhere('slug', 'cambi-direzione-zigzag');
        $slalom = Drill::firstWhere('slug', 'conduzione-slalom');

        $this->actingAs($coach)->get(route('teams.attendances.edit', [$team, 'data' => '2026-10-14']))->assertOk()
            ->assertSee('Esercizi fatti in allenamento')->assertSee('<optgroup label="Cambio di direzione">', false);

        $this->put(route('teams.attendances.update', $team), [
            'data' => '2026-10-14',
            'players' => [$player->id => '1'],
            'drills' => [$zigzag->id, '', $slalom->id, $zigzag->id],
        ])->assertRedirect(route('teams.attendances.show', $team));
        $this->assertSame([$zigzag->id, $slalom->id], TrainingDrill::orderBy('id')->pluck('drill_id')->all());

        $this->get(route('teams.attendances.show', $team))->assertOk()
            ->assertSee('Cambi di direzione tra i coni (zig-zag)')->assertSee('Conduzione a slalom');
        $this->get(route('teams.attendances.edit', [$team, 'data' => '2026-10-14']))->assertOk()
            ->assertSee('<option value="'.$zigzag->id.'" selected', false);

        // Correggere l'appello sostituisce gli esercizi; un esercizio inesistente è rifiutato.
        $this->put(route('teams.attendances.update', $team), ['data' => '2026-10-14', 'players' => [$player->id => '1'], 'drills' => [$slalom->id]]);
        $this->assertSame([$slalom->id], TrainingDrill::pluck('drill_id')->all());
        $this->put(route('teams.attendances.update', $team), ['data' => '2026-10-14', 'players' => [$player->id => '1'], 'drills' => [9999]])
            ->assertSessionHasErrors('drills.0');

        $this->delete(route('teams.attendances.destroy', $team), ['data' => '2026-10-14']);
        $this->assertSame(0, TrainingDrill::count());
    }
}
