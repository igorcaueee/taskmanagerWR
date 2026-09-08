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
            // CNPJ/CPF (só dígitos) do destinatário da nota, extraído de <dest> no
            // xml_content. Usado para separar matriz x filial na consulta: a
            // distribuição de DF-e consultada pela matriz também devolve notas das
            // filiais (mesma raiz de CNPJ), e sem o destinatário não dá pra distinguir
            // uma compra da matriz de uma nota própria de outra filial. Fica null para
            // resumos (resNFe/resCTe) e eventos, que não trazem o grupo <dest>.
            $table->string('destinatario_doc', 14)->nullable()->after('emitente_doc');
            $table->index(['cliente_id', 'destinatario_doc']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            $table->dropIndex(['cliente_id', 'destinatario_doc']);
            $table->dropColumn('destinatario_doc');
        });
    }
};
