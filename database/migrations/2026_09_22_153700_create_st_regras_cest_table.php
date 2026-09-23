<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de regras de ICMS-ST por UF+CEST — módulo de antecipação
     * tributária. Seedado fielmente a partir dos dicionários REGRAS_CEST
     * (RS) e REGRAS_CEST_MG (MG) dos motores Python já validados por Igor
     * Caue (ver StRegrasCestSeeder). O motor de cálculo NUNCA infere estes
     * valores — só lê o que está cadastrado aqui.
     */
    public function up(): void
    {
        Schema::create('st_regras_cest', function (Blueprint $table) {
            $table->id();
            $table->enum('uf', ['RS', 'MG']);
            $table->string('cest', 9);
            $table->string('segmento');
            $table->text('descricao');
            // RS: MVA varia pela alíquota interestadual (12% ou 4%) do XML.
            $table->decimal('mva_12_pct', 6, 2)->nullable();
            $table->decimal('mva_4_pct', 6, 2)->nullable();
            // MG: MVA é uma única coluna, independente da alíquota interestadual.
            $table->decimal('mva_pct', 6, 2)->nullable();
            $table->decimal('aliquota_interna_pct', 5, 2);
            $table->enum('adicional_tipo', ['nenhum', 'AMPARA_RS', 'FEM_MG'])->default('nenhum');
            $table->decimal('adicional_pct', 5, 2)->nullable();
            // false = adicional (FEM/MG) provável mas pendente de confirmação expressa
            // antes de somar automaticamente (ex.: perfumaria 33.03-33.07 sem exceção).
            $table->boolean('adicional_confirmado')->default(true);
            // false = motor bloqueia o cálculo do ICMS-ST deste CEST (alíquota interna
            // ainda não confirmada contra a fonte oficial).
            $table->boolean('aliquota_confirmada')->default(true);
            // UFs de origem para as quais este CEST não se aplica (sem acordo/protocolo)
            // — não dispensa o ICMS-ST, só muda a mensagem no campo "responsavel".
            $table->json('nao_aplica_uf_origem')->nullable();
            $table->text('fonte_legal')->nullable();
            $table->timestamps();

            $table->unique(['uf', 'cest']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_regras_cest');
    }
};
