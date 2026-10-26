<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // campionato oppure gironi: i tornei già creati restano campionati.
            $table->string('format')->default('campionato')->after('season');
            $table->unsignedTinyInteger('groups_count')->nullable()->after('format');
            // Quante squadre per girone passano alla fase finale.
            $table->unsignedTinyInteger('qualifiers_per_group')->nullable()->after('groups_count');
        });

        Schema::table('games', function (Blueprint $table) {
            // Turno della fase finale (quarti, semifinale...): null per le partite di campionato o dei gironi.
            $table->string('stage')->nullable()->after('matchday');
            // Posizione nel tabellone: le vincenti delle partite 1 e 2 si incontrano nel turno dopo, e così via.
            $table->unsignedTinyInteger('bracket_slot')->nullable()->after('stage');
            // Rigori, solo se una partita a eliminazione finisce in parità.
            $table->unsignedTinyInteger('home_penalties')->nullable()->after('away_score');
            $table->unsignedTinyInteger('away_penalties')->nullable()->after('home_penalties');
        });

        Schema::table('teams', function (Blueprint $table) {
            // Lettera del girone (A, B, C...), solo nei tornei a gironi.
            $table->string('group_name', 1)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('games', fn (Blueprint $table) => $table->dropColumn(['stage', 'bracket_slot', 'home_penalties', 'away_penalties']));
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('group_name'));
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn(['format', 'groups_count', 'qualifiers_per_group']));
    }
};
