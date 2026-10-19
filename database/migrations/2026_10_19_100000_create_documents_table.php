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
        // Documenti caricati da allenatori e admin. Il file sta in storage/app/private (non pubblico)
        // e si scarica solo passando dal controller, che controlla chi lo può vedere.
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Vero = lo vedono tutti gli utenti dell'app; falso = solo le persone scelte (tabella document_user).
            $table->boolean('for_everyone')->default(true);
            $table->timestamps();
        });

        Schema::create('document_user', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_user');
        Schema::dropIfExists('documents');
    }
};
