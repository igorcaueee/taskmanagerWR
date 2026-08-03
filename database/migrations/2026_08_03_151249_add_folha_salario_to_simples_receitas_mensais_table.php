<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valor da folha de salário do período — obrigatório no TRANSDECLARACAO11
 * quando alguma atividade lançada é "sujeita ao fator r" (ids 10/11/12/29
 * do catálogo, Anexo V), confirmado em produção (2026-08-03): a API rejeitou
 * a transmissão com "Existe atividade com folha de salário obrigatória.
 * Informar o(s) período(s): 06/2026".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simples_receitas_mensais', function (Blueprint $table) {
            $table->decimal('folha_salario', 15, 2)->nullable()->after('regime_apuracao');
        });
    }

    public function down(): void
    {
        Schema::table('simples_receitas_mensais', function (Blueprint $table) {
            $table->dropColumn('folha_salario');
        });
    }
};
