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
        Schema::create('ideias', function (Blueprint $table) {
            $table->id();
            $table->text('descricao');
            $table->foreignId('colaborador_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('status', ['pendente', 'em_analise', 'aprovada', 'concluida', 'descartada'])->default('pendente');
            $table->date('data_conclusao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideias');
    }
};
