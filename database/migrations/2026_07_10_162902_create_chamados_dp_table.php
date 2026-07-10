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
        Schema::create('chamados_dp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('portal_usuario_id')->constrained('portal_usuarios')->cascadeOnDelete();
            $table->enum('tipo', ['admissao', 'demissao']);
            $table->string('nome_colaborador');
            $table->string('cpf')->nullable();
            $table->string('cargo_funcao')->nullable();
            $table->date('data_evento');
            $table->string('motivo')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chamados_dp');
    }
};
