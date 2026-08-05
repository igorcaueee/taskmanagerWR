<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cliente_certificados_nfse', function (Blueprint $table) {
            // Sequência de NSU independente da de NF-e (ultimo_nsu_nfe) — o
            // CTeDistribuicaoDFe é um webservice nacional separado do
            // NFeDistribuicaoDFe, com sua própria numeração de NSU por CNPJ.
            $table->unsignedBigInteger('ultimo_nsu_cte')->nullable()->after('ultimo_nsu_nfe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cliente_certificados_nfse', function (Blueprint $table) {
            $table->dropColumn('ultimo_nsu_cte');
        });
    }
};
