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
        // Moduli, adesioni e regolamenti del CSI finiscono anche in Documenti, visibili a tutti.
        Schema::table('csi_documents', function (Blueprint $table) {
            $table->foreignId('document_id')->nullable()->after('path')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csi_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_id');
        });
    }
};
