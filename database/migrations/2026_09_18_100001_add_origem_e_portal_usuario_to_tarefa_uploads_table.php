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
            $table->string('origem')->default('empresa')->after('cliente_id');
            $table->foreignId('enviado_por_portal_usuario_id')->nullable()->after('enviado_por')
                ->constrained('portal_usuarios')->nullOnDelete();
        });

        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropForeign(['tarefa_id']);
            $table->foreignId('tarefa_id')->nullable()->change();
            $table->foreign('tarefa_id')->references('id')->on('tarefas')->cascadeOnDelete();

            $table->dropForeign(['enviado_por']);
            $table->foreignId('enviado_por')->nullable()->change();
            $table->foreign('enviado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropForeign(['enviado_por_portal_usuario_id']);
            $table->dropColumn(['origem', 'enviado_por_portal_usuario_id']);

            $table->dropForeign(['tarefa_id']);
            $table->foreignId('tarefa_id')->nullable(false)->change();
            $table->foreign('tarefa_id')->references('id')->on('tarefas')->cascadeOnDelete();

            $table->dropForeign(['enviado_por']);
            $table->foreignId('enviado_por')->nullable(false)->change();
            $table->foreign('enviado_por')->references('id')->on('usuarios')->restrictOnDelete();
        });
    }
};
