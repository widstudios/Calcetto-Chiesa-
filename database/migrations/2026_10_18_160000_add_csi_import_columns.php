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
        // Codice gara del CSI (es. GXC10B3): lega la partita al comunicato che la aggiorna.
        Schema::table('games', function (Blueprint $table) {
            $table->string('csi_code', 20)->nullable()->after('status');
            $table->index(['tournament_id', 'csi_code']);
        });

        // Esito della lettura di ogni comunicato.
        Schema::table('csi_documents', function (Blueprint $table) {
            $table->timestamp('imported_at')->nullable()->after('downloaded_at');
            $table->unsignedInteger('games_found')->nullable()->after('imported_at');
            $table->unsignedInteger('games_saved')->nullable()->after('games_found');
            $table->text('import_note')->nullable()->after('games_saved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['tournament_id', 'csi_code']);
            $table->dropColumn('csi_code');
        });
        Schema::table('csi_documents', function (Blueprint $table) {
            $table->dropColumn(['imported_at', 'games_found', 'games_saved', 'import_note']);
        });
    }
};
