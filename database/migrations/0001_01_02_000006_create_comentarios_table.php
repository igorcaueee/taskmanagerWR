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
		Schema::create('comentarios', function (Blueprint $table) {
			$table->id();
			$table->foreignId('tarefa_id')->constrained('tarefas')->cascadeOnDelete();
			$table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
			$table->text('comentario');
			$table->timestamps();

			$table->index('tarefa_id');
			$table->index('usuario_id');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('comentarios');
	}
};
