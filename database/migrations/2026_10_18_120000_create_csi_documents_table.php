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
        // Comunicati e documenti scaricati dalla cartella Google Drive del CSI Roma.
        Schema::create('csi_documents', function (Blueprint $table) {
            $table->id();
            $table->string('drive_id')->unique();
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('drive_modified_at')->nullable();
            $table->string('path')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csi_documents');
    }
};
