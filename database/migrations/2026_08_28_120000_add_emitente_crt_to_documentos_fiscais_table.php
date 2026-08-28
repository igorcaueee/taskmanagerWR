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
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            // CRT (Código de Regime Tributário) do emitente, lido da tag <emit><CRT> do
            // XML da NF-e: 1 = Simples Nacional, 2 = Simples Nacional (excesso de
            // sublimite de receita), 3 = Regime Normal, 4 = MEI. Guardado aqui na
            // sincronização (e via `fiscal:backfill-emitente-crt` pros já existentes)
            // pra que o dashboard "Top Fornecedores (Simples Nacional)" seja um
            // GROUP BY em SQL, sem reabrir o xml_content de cada nota do período.
            // Fica null em resumos (resNFe) e documentos antigos ainda sem XML completo.
            $table->tinyInteger('emitente_crt')->nullable()->after('tp_nf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            $table->dropColumn('emitente_crt');
        });
    }
};
