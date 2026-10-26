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
        // Arbitri: il telefono resta nel gestionale, non va mai sul sito pubblico.
        Schema::create('referees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Chi arbitra la partita: se l'arbitro viene eliminato la partita resta senza arbitro.
        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('referee_id')->nullable()->after('away_team_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referee_id');
        });
        Schema::dropIfExists('referees');
    }
};
