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
            // 0 = entrada, 1 = saída — campo tpNF do próprio XML. Não dá pra inferir a
            // direção comparando o emitente com o CNPJ do cliente: uma NF-e de entrada
            // emitida pelo próprio destinatário (compra de produtor rural, por exemplo)
            // tem o cliente como emitente mesmo sendo uma entrada de mercadoria.
            $table->tinyInteger('tp_nf')->nullable()->after('situacao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            $table->dropColumn('tp_nf');
        });
    }
};
