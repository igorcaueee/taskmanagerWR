<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uma guia de recolhimento (GNRE nacional para RS, DAE/SIARE para MG)
     * por NF-e (1:1 nesta fase — ver PreparadorGnreRs/PreparadorDaeMg). O
     * sistema só prepara os dados; a emissão em si é manual no portal
     * oficial, e o PDF resultante é anexado de volta aqui.
     */
    public function up(): void
    {
        Schema::create('st_guias', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['GNRE_RS', 'DAE_MG']);
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('chave_acesso', 44);
            $table->string('codigo_receita');
            $table->string('descricao_receita')->nullable();
            $table->decimal('valor_icms_st', 15, 2)->default(0);
            $table->decimal('valor_adicional', 15, 2)->default(0);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            // Só preenchido para DAE/MG -- Período de Referência = mês/ano da
            // data de vencimento (nunca emissão/pagamento).
            $table->string('periodo_referencia_mes')->nullable();
            $table->string('periodo_referencia_ano')->nullable();
            $table->enum('status', ['RASCUNHO', 'PRONTA_PARA_EMISSAO', 'EMITIDA', 'PAGA'])->default('RASCUNHO');
            $table->string('pdf_guia_path')->nullable();
            $table->string('pdf_comprovante_path')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique('chave_acesso');
            $table->index('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_guias');
    }
};
