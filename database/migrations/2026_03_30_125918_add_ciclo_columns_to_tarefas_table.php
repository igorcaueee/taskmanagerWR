<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->foreignId('ciclo_id')
                ->nullable()
                ->after('data_vencimento')
                ->constrained('ciclos')
                ->nullOnDelete();

            $table->boolean('passou_ciclo')
                ->default(false)
                ->after('ciclo_id')
                ->comment('Indica que a tarefa foi transferida de um ciclo anterior');

            $table->index('ciclo_id');
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
            $table->dropIndex(['ciclo_id']);
            $table->dropColumn(['ciclo_id', 'passou_ciclo']);
        });
    }
};
