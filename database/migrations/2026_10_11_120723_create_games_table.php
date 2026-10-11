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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday')->nullable();
            $table->dateTime('kickoff_at')->nullable();
            $table->string('venue')->nullable();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->string('status')->default('programmata');
            $table->timestamps();

            $table->index(['tournament_id', 'kickoff_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
