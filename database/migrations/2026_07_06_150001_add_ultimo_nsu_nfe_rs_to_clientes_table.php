<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NSU do webservice de contabilistas da SEFAZ-RS é por CNPJ consultado, não por
 * certificado (que aqui é único, da contabilidade) — por isso fica no cliente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->bigInteger('ultimo_nsu_nfe_rs')->default(0)->after('vencimento_certificado');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('ultimo_nsu_nfe_rs');
        });
    }
};
