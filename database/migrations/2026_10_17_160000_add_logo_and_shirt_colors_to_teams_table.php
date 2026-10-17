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
        // Stemma (file in public/uploads) e colori della maglia di ogni squadra.
        Schema::table('teams', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('coach_name');
            $table->string('shirt_color', 7)->nullable()->after('logo');
            $table->string('shirt_color_secondary', 7)->nullable()->after('shirt_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['logo', 'shirt_color', 'shirt_color_secondary']);
        });
    }
};
