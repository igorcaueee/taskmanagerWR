<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cabeçalho de uma apuração MIT "com movimento" em rascunho ou já
 * transmitida — 1 registro por cliente+ano+mês. O caso "sem movimento" não
 * usa rascunho (é encerrado direto, ver MitService::encerrarApuracaoSemMovimento);
 * esta tabela só existe para o fluxo com débitos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mit_apuracoes_rascunho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedSmallInteger('ano_apuracao');
            $table->unsignedTinyInteger('mes_apuracao');

            $table->unsignedTinyInteger('qualificacao_pj');
            // Obrigatório só quando qualificacao_pj != 11 (regra do manual do MIT).
            $table->unsignedTinyInteger('tributacao_lucro')->nullable();
            $table->unsignedTinyInteger('variacoes_monetarias')->default(1);
            $table->unsignedTinyInteger('regime_pis_cofins')->nullable();
            $table->string('cpf_responsavel', 11);

            // Só relevante quando tributacao_lucro = 1 (Lucro Real Anual).
            $table->boolean('balanco_irpj')->nullable();
            $table->boolean('balanco_csll')->nullable();

            $table->boolean('sem_movimento')->default(false);

            $table->enum('status', ['rascunho', 'transmitida', 'erro'])->default('rascunho');
            $table->string('id_apuracao_serpro', 20)->nullable();
            $table->text('mensagem_erro')->nullable();
            $table->timestamp('encerrado_em')->nullable();

            $table->timestamps();

            $table->unique(['cliente_id', 'ano_apuracao', 'mes_apuracao'], 'mit_apur_cliente_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mit_apuracoes_rascunho');
    }
};
