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
            // Papel do cliente consultado dentro do CT-e (Emitente/Tomador/Remetente/
            // Destinatário/Expedidor/Recebedor) — diferente de NF-e, o CT-e pode citar a
            // empresa como remetente/destinatário sem ela ser quem contratou e paga o
            // frete (o Tomador do Serviço). Só preenchido para tipo='cte'.
            $table->string('papel_cte', 20)->nullable()->after('tp_nf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            $table->dropColumn('papel_cte');
        });
    }
};
