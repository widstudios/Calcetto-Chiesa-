<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un arbitro può avere un account per vedere le partite che arbitra.
     */
    public function up(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('phone')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
