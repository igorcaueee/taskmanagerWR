<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tratamento tributário (isenção/redução/substituição/monofásica/etc.) por
 * tributo dentro de cada atividade — réplica dos dropdowns por tributo
 * (COFINS, CSLL, ICMS, INSS/CPP, IRPJ, PIS, IPI, ISS) vistos na etapa
 * "Receitas" do e-CAC. Um registro por tributo aplicável à atividade; se não
 * houver linha, entende-se tributação normal (sem ajuste).
 *
 * "tipo_ajuste" usa os valores da tabela oficial "Qualificação Tributária"
 * (imunidade=1, lancamento_oficio=3, substituicao_tributaria=8,
 * tributacao_monofasica=9, antecipacao_encerramento=10, retencao_iss=11) mais
 * "isencao"/"reducao" (tabela "Identificador de isenção/redução": normal=1,
 * cesta_basica=2) e "exigibilidade_suspensa" (tabela "Motivos de
 * Exigibilidade Suspensa", 1-6) — ver App\Support\PgdasdAtividades.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simples_receitas_atividades_tributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simples_receita_atividade_id');
            $table->foreign('simples_receita_atividade_id', 'srat_atividade_id_foreign')
                ->references('id')->on('simples_receitas_atividades')
                ->cascadeOnDelete();
            $table->integer('cod_tributo'); // 1001-1010, ver PgdasdAtividades::NOMES_TRIBUTOS
            $table->enum('tipo_ajuste', [
                'normal',
                'isencao',
                'reducao',
                'imunidade',
                'lancamento_oficio',
                'substituicao_tributaria',
                'tributacao_monofasica',
                'antecipacao_encerramento',
                'retencao_iss',
                'exigibilidade_suspensa',
            ])->default('normal');
            $table->unsignedTinyInteger('identificador_isencao')->nullable(); // 1=Normal, 2=Cesta básica (isencao/reducao)
            $table->decimal('percentual_reducao', 5, 2)->nullable(); // só para tipo_ajuste=reducao
            $table->unsignedTinyInteger('motivo_suspensao')->nullable(); // 1-6, só para exigibilidade_suspensa
            $table->decimal('valor', 15, 2)->default(0); // valor sujeito ao ajuste
            $table->timestamps();

            $table->unique(['simples_receita_atividade_id', 'cod_tributo'], 'srat_atividade_tributo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simples_receitas_atividades_tributos');
    }
};
