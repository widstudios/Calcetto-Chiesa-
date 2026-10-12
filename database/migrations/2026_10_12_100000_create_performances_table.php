<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabellino: gol e voto di ogni calciatore in una partita, più il migliore in campo.
     */
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('goals')->default(0);
            $table->decimal('rating', 3, 1)->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('mvp_player_id')->nullable()->after('away_score')->constrained('players')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mvp_player_id');
        });
        Schema::dropIfExists('performances');
    }
};
