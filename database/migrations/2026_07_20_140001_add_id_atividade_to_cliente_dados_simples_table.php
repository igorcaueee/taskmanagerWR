<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * idAtividade é o código de atividade do catálogo interno da SERPRO/RFB usado
 * no payload do TRANSDECLARACAO11 — diferente do CNAE, não tem uma tabela de
 * conversão pública confirmada; precisa ser preenchido/validado manualmente
 * por cliente antes da primeira transmissão real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_dados_simples', function (Blueprint $table) {
            $table->integer('id_atividade')->nullable()->after('cnae_principal');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_dados_simples', function (Blueprint $table) {
            $table->dropColumn('id_atividade');
        });
    }
};
