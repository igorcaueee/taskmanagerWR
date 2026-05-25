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
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->timestamp('visualizado_em')->nullable()->after('baixado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropColumn('visualizado_em');
        });
    }
};
