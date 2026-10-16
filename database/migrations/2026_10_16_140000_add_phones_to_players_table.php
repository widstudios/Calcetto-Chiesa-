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
        Schema::table('players', function (Blueprint $table) {
            // Cellulari di contatto (per i ragazzi, quelli dei genitori): visibili solo nel pannello.
            $table->string('phone', 30)->nullable()->after('birth_year');
            $table->string('phone_2', 30)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_2']);
        });
    }
};
