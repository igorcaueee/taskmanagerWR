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
		Schema::create('tarefas', function (Blueprint $table) {
			$table->id();
			$table->string('titulo');
			$table->text('descricao')->nullable();
			$table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
			$table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
			$table->foreignId('etapa_id')->constrained('etapas')->restrictOnDelete();
			$table->foreignId('responsavel_id')->nullable()->constrained('usuarios')->nullOnDelete();
			$table->foreignId('criado_por')->constrained('usuarios')->restrictOnDelete();
			$table->date('data_vencimento');
			$table->timestamp('data_conclusao')->nullable();
			$table->tinyInteger('prioridade')->default(1);
			$table->boolean('atrasada')->default(false);

			// Campos de recorrência
			$table->boolean('recorrente')->default(false);
			$table->enum('frequencia', ['nenhuma', 'diaria', 'semanal', 'mensal', 'anual'])->default('nenhuma');
			$table->integer('intervalo')->nullable()->comment('a cada quantos dias/semanas/meses');
			$table->foreignId('tarefa_original_id')->nullable()->constrained('tarefas')->nullOnDelete();
			$table->date('data_proxima_geracao')->nullable();

			$table->timestamps();

			$table->index('cliente_id');
			$table->index('departamento_id');
			$table->index('etapa_id');
			$table->index('recorrente');
			$table->index('tarefa_original_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('tarefas');
	}
};
