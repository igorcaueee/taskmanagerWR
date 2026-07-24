<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabeçalho de uma DEFIS (declaração anual do Simples Nacional) em rascunho
 * ou já transmitida — 1 registro por cliente+ano. Suporta só um
 * estabelecimento (a matriz, cpfcnpj do cliente), mesma simplificação já
 * usada no PGDASD (PgdasdService::montarDeclaracao); clientes com filiais
 * distintas na DEFIS precisam de lançamento manual pelo e-CAC por enquanto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defis_declaracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ano_calendario');

            // Só obrigatório para ano_calendario < 2025 (regra da própria DEFIS).
            $table->unsignedTinyInteger('inatividade')->nullable();

            $table->decimal('ganho_capital', 15, 2)->default(0);
            $table->unsignedInteger('qtd_empregado_inicial')->default(0);
            $table->unsignedInteger('qtd_empregado_final')->default(0);
            $table->decimal('lucro_contabil', 15, 2)->nullable();
            $table->decimal('receita_exportacao_direta', 15, 2)->default(0);
            $table->decimal('participacao_cotas_tesouraria', 15, 2)->nullable();
            $table->decimal('ganho_renda_variavel', 15, 2)->default(0);

            // Estabelecimento único (matriz) — campos financeiros do período.
            $table->decimal('estoque_inicial', 15, 2)->default(0);
            $table->decimal('estoque_final', 15, 2)->default(0);
            $table->decimal('saldo_caixa_inicial', 15, 2)->default(0);
            $table->decimal('saldo_caixa_final', 15, 2)->default(0);
            $table->decimal('aquisicoes_mercado_interno', 15, 2)->default(0);
            $table->decimal('importacoes', 15, 2)->default(0);
            $table->decimal('total_entradas_por_transferencia', 15, 2)->default(0);
            $table->decimal('total_saidas_por_transferencia', 15, 2)->default(0);
            $table->decimal('total_devolucoes_vendas', 15, 2)->default(0);
            $table->decimal('total_entradas', 15, 2)->default(0);
            $table->decimal('total_devolucoes_compras', 15, 2)->default(0);
            $table->decimal('total_despesas', 15, 2)->default(0);
            $table->decimal('iss_retidos_fonte', 15, 2)->nullable();
            $table->decimal('prestacoes_servico_comunicacao', 15, 2)->nullable();
            $table->decimal('prestacoes_servico_transporte', 15, 2)->nullable();

            $table->enum('status', ['rascunho', 'transmitida', 'erro'])->default('rascunho');
            $table->string('id_defis', 15)->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('transmitido_em')->nullable();

            $table->timestamps();

            $table->unique(['cliente_id', 'ano_calendario'], 'defis_decl_cliente_ano_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defis_declaracoes');
    }
};
