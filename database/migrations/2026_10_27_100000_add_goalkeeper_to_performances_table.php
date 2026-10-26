<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            // Chi ha giocato in porta: serve per la classifica del miglior portiere (gol subiti).
            $table->boolean('goalkeeper')->default(false)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('performances', fn (Blueprint $table) => $table->dropColumn('goalkeeper'));
    }
};
