<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alguns CEST são reconhecidos nacionalmente (Convênio ICMS 142/18) mas
     * deixaram de ser sujeitos a ST num Estado específico por decreto (ex.:
     * Autopeças no RS, revogado pelo Decreto nº 57.848/2024, efeitos desde
     * 01/11/2024 — o CEST continua de indicação obrigatória na nota, só não
     * gera mais antecipação de ST). Isso é diferente de "CEST não localizado
     * na tabela" (segmento nunca implementado aqui) -- é uma resposta
     * definitiva e pesquisada, não uma pendência.
     */
    public function up(): void
    {
        Schema::table('st_regras_cest', function (Blueprint $table) {
            $table->enum('situacao', ['vigente', 'revogada'])->default('vigente')->after('cest');
            $table->date('revogada_desde')->nullable()->after('situacao');
            // Numa regra revogada não existe alíquota interna aplicável (não há mais ST) --
            // deixar NULL em vez de forçar um "0.00" que passaria a impressão de valor real.
            $table->decimal('aliquota_interna_pct', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('st_regras_cest', function (Blueprint $table) {
            $table->dropColumn(['situacao', 'revogada_desde']);
            $table->decimal('aliquota_interna_pct', 5, 2)->nullable(false)->change();
        });
    }
};
