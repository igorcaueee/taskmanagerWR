<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Atividades cuja descrição diz "devido a outro(s) Município(s)" (10, 13, 16,
 * 19, 22, 25, 40 — ver PgdasdAtividades) exigem informar em qual Município o
 * ISS é devido — confirmado em produção (2026-09-14): a API rejeitou o
 * TRANSDECLARACAO11 com "Campo UF inválido na atividade 16" por essa
 * informação estar faltando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->string('uf', 2)->nullable()->after('valor');
            $table->string('codigo_municipio_ibge', 7)->nullable()->after('uf');
        });
    }

    public function down(): void
    {
        Schema::table('simples_receitas_atividades', function (Blueprint $table) {
            $table->dropColumn(['uf', 'codigo_municipio_ibge']);
        });
    }
};
