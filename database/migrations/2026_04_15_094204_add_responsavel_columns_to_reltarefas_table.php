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
        Schema::table('reltarefas', function (Blueprint $table) {
            $table->foreignId('etapa_nova_id')->nullable()->change();
            $table->foreignId('responsavel_anterior_id')->nullable()->after('etapa_nova_id')->constrained('usuarios')->nullOnDelete();
            $table->foreignId('responsavel_novo_id')->nullable()->after('responsavel_anterior_id')->constrained('usuarios')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reltarefas', function (Blueprint $table) {
            $table->dropForeign(['responsavel_novo_id']);
            $table->dropForeign(['responsavel_anterior_id']);
            $table->dropColumn(['responsavel_anterior_id', 'responsavel_novo_id']);
            $table->foreignId('etapa_nova_id')->nullable(false)->change();
        });
    }
};
