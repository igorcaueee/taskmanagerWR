<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tarefa_cliente', function (Blueprint $table) {
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->primary(['tarefa_id', 'cliente_id']);
        });

        // Migrar dados existentes da coluna cliente_id para a pivot
        DB::table('tarefa_cliente')->insertUsing(
            ['tarefa_id', 'cliente_id'],
            DB::table('tarefas')->whereNotNull('cliente_id')->select('id', 'cliente_id')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefa_cliente');
    }
};
