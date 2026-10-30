<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // Turni della fase finale con andata e ritorno, es. ["semifinale"].
            $table->json('two_leg_stages')->nullable()->after('qualifiers_per_group');
        });

        Schema::table('games', function (Blueprint $table) {
            // 1 = andata, 2 = ritorno; null = partita secca.
            $table->unsignedTinyInteger('leg')->nullable()->after('bracket_slot');
        });
    }

    public function down(): void
    {
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn('leg'));
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn('two_leg_stages'));
    }
};
