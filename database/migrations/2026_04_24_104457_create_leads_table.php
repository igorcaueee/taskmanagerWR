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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('empresa')->nullable();
            $table->decimal('faturamento', 15, 2)->nullable();
            $table->string('servico')->nullable();
            $table->decimal('honorario', 15, 2)->nullable();
            $table->text('possibilidade')->nullable();
            $table->foreignId('etapa_funil_id')->constrained('etapas_funil')->restrictOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->enum('origem', ['manual', 'formulario'])->default('manual');
            $table->text('observacoes')->nullable();
            $table->foreignId('convertido_cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
