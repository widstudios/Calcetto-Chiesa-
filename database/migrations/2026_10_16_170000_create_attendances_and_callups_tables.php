<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Appello degli allenamenti: una riga per calciatore e data.
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('present');
            $table->timestamps();
            $table->unique(['player_id', 'date']);
            $table->index(['team_id', 'date']);
        });

        // Distinta di gara: i convocati di una squadra per una partita.
        Schema::create('callups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('shirt_number')->nullable();
            // Capitano o vice capitano, vuoto per gli altri.
            $table->string('captain')->nullable();
            $table->timestamps();
            $table->unique(['game_id', 'player_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('callups');
        Schema::dropIfExists('attendances');
    }
};
