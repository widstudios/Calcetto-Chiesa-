<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            // csi oppure proprio: i tornei già creati restano con il regolamento CSI.
            $table->string('rules_type')->default('csi')->after('lineup_max_players');
            // Regolamento proprio: testo scritto dall'admin e/o PDF caricato (in public/uploads).
            $table->text('rules_text')->nullable()->after('rules_type');
            $table->string('rules_pdf')->nullable()->after('rules_text');
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn(['rules_type', 'rules_text', 'rules_pdf']));
    }
};
