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
        // MySQL usa esse índice unique pra apoiar a foreign key de cliente_id
        // (é a primeira coluna do composto) — não deixa dropar sem antes criar
        // outro índice que sirva de apoio pra FK (erro 1553, confirmado em
        // produção 2026-08-07: "Cannot drop index ... needed in a foreign key
        // constraint").
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->index('cliente_id', 'sra_cliente_id_index');
        });

        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->dropUnique('sra_cliente_periodo_atividade_unique');
        });
    }

    public function down(): void
    {
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->unique(['cliente_id', 'periodo_apuracao', 'id_atividade'], 'sra_cliente_periodo_atividade_unique');
        });

        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->dropIndex('sra_cliente_id_index');
        });
    }
};
