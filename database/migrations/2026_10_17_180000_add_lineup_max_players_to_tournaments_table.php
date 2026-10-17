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
        // Massimo di giocatori in distinta per questa categoria; vuoto = quello delle Impostazioni.
        Schema::table('tournaments', function (Blueprint $table) {
            $table->unsignedTinyInteger('lineup_max_players')->nullable()->after('ends_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropColumn('lineup_max_players');
        });
    }
};
