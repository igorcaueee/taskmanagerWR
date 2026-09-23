<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Um registro por item de NF-e processado pelo motor de ICMS-ST
     * (antecipação tributária pelo destinatário). Recalcular um período é
     * sempre um upsert por (chave_acesso, nfe_item) — nunca duplica linha.
     */
    public function up(): void
    {
        Schema::create('st_calculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('chave_acesso', 44);
            $table->string('nfe_numero')->nullable();
            $table->string('nfe_item');
            $table->string('ncm', 8)->nullable();
            $table->string('produto')->nullable();

            // CEST do XML nunca é sobrescrito; cest_usado é o que efetivamente
            // entrou no cálculo (pode ser igual, atribuído ou corrigido).
            $table->string('cest_xml', 9)->nullable();
            $table->string('cest_usado', 9)->nullable();
            $table->enum('cest_origem', [
                'xml',
                'atribuido_manualmente',
                'corrigido_manualmente',
                'xml_confirmado_manualmente',
            ])->nullable();

            $table->string('segmento')->nullable();
            $table->text('cest_descricao')->nullable();
            $table->string('cfop', 4)->nullable();
            $table->string('uf_origem', 2)->nullable();
            $table->string('uf_destino', 2)->nullable();

            $table->decimal('vprod', 15, 2)->nullable();
            $table->decimal('vbc_origem', 15, 2)->nullable();
            $table->decimal('base_operacao_usada', 15, 2)->nullable();
            $table->decimal('vipi', 15, 2)->nullable();
            $table->decimal('aliq_interestadual_pct', 5, 2)->nullable();
            $table->decimal('mva_aplicada_pct', 6, 2)->nullable();
            $table->decimal('base_st_calculada', 15, 2)->nullable();
            $table->decimal('aliquota_interna_pct', 5, 2)->nullable();
            $table->decimal('icms_proprio', 15, 2)->nullable();
            $table->decimal('icms_st_devido', 15, 2)->nullable();
            $table->enum('adicional_tipo', ['nenhum', 'AMPARA_RS', 'FEM_MG'])->default('nenhum');
            $table->decimal('adicional_valor', 15, 2)->nullable();
            $table->decimal('total_a_recolher', 15, 2)->nullable();
            $table->text('responsavel')->nullable();

            $table->enum('status', [
                'calculado',
                'pendente_cest',
                'pendente_aliquota',
                'pendente_reducao_base',
                'pendente_fem',
                'uf_nao_suportada',
                'st_ja_destacada_no_xml',
            ]);
            $table->text('status_detalhe')->nullable();

            $table->timestamps();

            $table->unique(['chave_acesso', 'nfe_item']);
            $table->index(['cliente_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_calculos');
    }
};
