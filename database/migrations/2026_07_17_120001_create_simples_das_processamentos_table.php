<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log de execução do processamento mensal de DAS por cliente/período — alimenta
 * o painel de acompanhamento e evita retransmitir uma declaração já enviada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simples_das_processamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('periodo_apuracao', 6); // YYYYMM
            $table->enum('status', ['pendente', 'sucesso', 'erro', 'ja_transmitido'])->default('pendente');
            $table->text('mensagem_erro')->nullable();
            $table->string('numero_recibo')->nullable();
            $table->string('das_pdf_path')->nullable();
            $table->timestamp('processado_em')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'periodo_apuracao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simples_das_processamentos');
    }
};
