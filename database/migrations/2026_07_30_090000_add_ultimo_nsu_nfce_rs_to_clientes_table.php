<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O webservice de contabilistas da SEFAZ-RS (NFeIntegracao) trata NF-e (mod=55)
 * e NFC-e (mod=65) como sequências de NSU independentes — por isso precisa de
 * uma coluna de controle separada de ultimo_nsu_nfe_rs, senão a paginação de
 * um modelo pisa na do outro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->bigInteger('ultimo_nsu_nfce_rs')->default(0)->after('ultimo_nsu_nfe_rs');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('ultimo_nsu_nfce_rs');
        });
    }
};
