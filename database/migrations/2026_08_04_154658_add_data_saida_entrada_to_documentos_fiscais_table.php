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
            // dhSaiEnt do XML — data em que a mercadoria de fato saiu/entrou, que pode cair
            // num mes diferente da emissao (dhEmi). Nem toda nota tem esse campo preenchido
            // (ex.: notas de servico) - nesse caso cai no fallback pra data_emissao.
            $table->date('data_saida_entrada')->nullable()->after('data_emissao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documentos_fiscais', function (Blueprint $table) {
            $table->dropColumn('data_saida_entrada');
        });
    }
};
