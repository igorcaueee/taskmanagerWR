<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NSU do webservice de contabilistas da SEFAZ-RS para CT-e (CTeIntegracao) é
 * por CNPJ consultado, não por certificado — mesma lógica de
 * ultimo_nsu_nfe_rs, em coluna separada porque é outro webservice/NSU.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->bigInteger('ultimo_nsu_cte_rs')->default(0)->after('ultimo_nsu_nfe_rs');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('ultimo_nsu_cte_rs');
        });
    }
};
