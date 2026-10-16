<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una squadra può avere più allenatori con account e un allenatore più squadre:
 * teams.coach_id diventa la tabella coach_team, tenendo le assegnazioni esistenti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_team', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['team_id', 'user_id']);
        });

        DB::table('coach_team')->insertUsing(
            ['team_id', 'user_id'],
            DB::table('teams')->whereNotNull('coach_id')->select('id', 'coach_id'),
        );

        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coach_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('coach_id')->nullable()->after('coach_name')->constrained('users')->nullOnDelete();
        });

        // Torna un solo allenatore per squadra: si tiene il primo.
        foreach (DB::table('coach_team')->orderBy('user_id')->get()->unique('team_id') as $row) {
            DB::table('teams')->where('id', $row->team_id)->update(['coach_id' => $row->user_id]);
        }

        Schema::dropIfExists('coach_team');
    }
};
