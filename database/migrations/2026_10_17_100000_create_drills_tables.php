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
        // Libreria degli esercizi per gli allenatori: come si fa, schema e video di esempio.
        Schema::create('drills', function (Blueprint $table) {
            $table->id();
            // Chiave fissa degli esercizi caricati con DrillSeeder (vuota per quelli aggiunti a mano).
            $table->string('slug')->nullable()->unique();
            $table->string('category');
            $table->string('name');
            $table->unsignedTinyInteger('age_min');
            $table->unsignedTinyInteger('age_max');
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->string('players')->nullable();
            $table->string('equipment')->nullable();
            $table->text('description');
            $table->text('setup')->nullable();
            // Svolgimento e consigli: una voce per riga.
            $table->text('steps')->nullable();
            $table->text('tips')->nullable();
            $table->string('diagram')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_title')->nullable();
            $table->string('video_channel')->nullable();
            $table->timestamps();
            $table->index(['category', 'name']);
        });

        // Esercizi fatti in un allenamento, segnati insieme all'appello.
        Schema::create('training_drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drill_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamps();
            $table->index(['team_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_drills');
        Schema::dropIfExists('drills');
    }
};
