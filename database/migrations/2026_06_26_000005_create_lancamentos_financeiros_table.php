<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lancamentos_financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('conta_azul_id')->nullable()->index();
            $table->unsignedBigInteger('conta_financeira_id')->nullable();
            $table->unsignedBigInteger('categoria_id')->nullable();
            $table->unsignedBigInteger('centro_custo_id')->nullable();
            $table->string('tipo')->default('credito'); // credito | debito
            $table->string('descricao')->nullable();
            $table->decimal('valor', 15, 2)->default(0);
            $table->date('data_vencimento')->nullable();
            $table->date('data_competencia')->nullable();
            $table->date('data_pagamento')->nullable();
            $table->string('status')->default('pendente'); // pendente | pago | cancelado | atrasado
            $table->boolean('conciliado')->default(false);
            $table->string('forma_pagamento')->nullable();
            $table->string('origem')->default('conta_azul'); // conta_azul | manual
            $table->timestamps();

            $table->foreign('conta_financeira_id')->references('id')->on('contas_financeiras')->nullOnDelete();
            $table->foreign('categoria_id')->references('id')->on('categorias_financeiras')->nullOnDelete();
            $table->foreign('centro_custo_id')->references('id')->on('centros_custo')->nullOnDelete();

            $table->unique(['cliente_id', 'conta_azul_id']);
            $table->index(['cliente_id', 'status']);
            $table->index(['cliente_id', 'tipo']);
            $table->index(['cliente_id', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lancamentos_financeiros');
    }
};
