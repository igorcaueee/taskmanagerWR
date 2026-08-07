<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A mesma atividade do PGDASD pode aparecer mais de uma vez no mesmo
 * período com tratamentos tributários diferentes — o relatório do Domínio
 * confirma isso na prática: "revenda com substituição tributária" (id=2)
 * pode vir quebrada em "Tabela 1 - Substituição tributária somente do
 * ICMS" e "Tabela 4 - Substituição tributária do PIS/PASEP, COFINS e do
 * ICMS", cada uma com sua própria receita e qualificação de tributos. O
 * TRANSDECLARACAO11 aceita normalmente repetir o mesmo idAtividade em
 * entradas distintas da lista "atividades" (confirmado revisando
 * PgdasdService::montarDeclaracao, que gera uma entrada por registro, não
 * por id_atividade único). A constraint impedia salvar esse caso real
 * (erro genérico "Falha ao salvar." na importação do Domínio, 2026-08-07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->dropUnique('sra_cliente_periodo_atividade_unique');
        });
    }

    public function down(): void
    {
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->unique(['cliente_id', 'periodo_apuracao', 'id_atividade'], 'sra_cliente_periodo_atividade_unique');
        });
    }
};
