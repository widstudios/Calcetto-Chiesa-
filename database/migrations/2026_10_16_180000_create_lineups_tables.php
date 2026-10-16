<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinte per squadra, anche senza una partita in calendario: prendono il posto di "callups".
     */
    public function up(): void
    {
        Schema::create('lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            // Partita del calendario, facoltativa (amichevoli, tornei esterni...).
            $table->foreignId('game_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('kickoff_at')->nullable();
            $table->string('opponent')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();
        });

        Schema::create('lineup_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lineup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('shirt_number')->nullable();
            // Capitano o vice capitano, vuoto per gli altri.
            $table->string('captain')->nullable();
            $table->timestamps();
            $table->unique(['lineup_id', 'player_id']);
        });

        // Le distinte già salvate per partita diventano distinte della squadra.
        $groups = DB::table('callups')->select('game_id', 'team_id')->distinct()->get();
        foreach ($groups as $group) {
            $game = DB::table('games')->find($group->game_id);
            $opponentId = $game->home_team_id == $group->team_id ? $game->away_team_id : $game->home_team_id;
            $lineupId = DB::table('lineups')->insertGetId([
                'team_id' => $group->team_id,
                'game_id' => $game->id,
                'kickoff_at' => $game->kickoff_at,
                'opponent' => DB::table('teams')->where('id', $opponentId)->value('name'),
                'venue' => $game->venue,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach (DB::table('callups')->where('game_id', $group->game_id)->where('team_id', $group->team_id)->get() as $row) {
                DB::table('lineup_players')->insert([
                    'lineup_id' => $lineupId,
                    'player_id' => $row->player_id,
                    'shirt_number' => $row->shirt_number,
                    'captain' => $row->captain,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('callups');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('callups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('shirt_number')->nullable();
            $table->string('captain')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'player_id']);
        });

        Schema::dropIfExists('lineup_players');
        Schema::dropIfExists('lineups');
    }
};
