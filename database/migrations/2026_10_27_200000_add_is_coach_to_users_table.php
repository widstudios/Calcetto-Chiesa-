<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un amministratore può essere anche allenatore di una o più squadre.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_coach')->default(false)->after('role');
        });

        // Gli admin che avevano già squadre assegnate (es. allenatori promossi) restano allenatori.
        DB::table('users')->where('role', 'admin')
            ->whereIn('id', DB::table('coach_team')->select('user_id'))
            ->update(['is_coach' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_coach');
        });
    }
};
