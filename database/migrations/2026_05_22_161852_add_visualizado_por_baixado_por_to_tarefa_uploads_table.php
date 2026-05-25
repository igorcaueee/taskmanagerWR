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
            $table->foreignId('visualizado_por')->nullable()->constrained('portal_usuarios')->nullOnDelete()->after('visualizado_em');
            $table->foreignId('baixado_por')->nullable()->constrained('portal_usuarios')->nullOnDelete()->after('baixado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropForeign(['visualizado_por']);
            $table->dropForeign(['baixado_por']);
            $table->dropColumn(['visualizado_por', 'baixado_por']);
        });
    }
};
