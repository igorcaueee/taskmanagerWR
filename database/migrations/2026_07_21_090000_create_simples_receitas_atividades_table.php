<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receita por atividade dentro de um período — réplica da etapa "Atividades" +
 * "Receitas" do assistente do PGDAS-D no e-CAC, onde o total do período é
 * quebrado por atividade selecionada (um cliente pode ter mais de uma
 * atividade cadastrada no Simples Nacional ao mesmo tempo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simples_receitas_atividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('periodo_apuracao', 6); // YYYYMM
            $table->unsignedTinyInteger('id_atividade'); // 1-43, catálogo App\Support\PgdasdAtividades
            $table->decimal('valor', 15, 2);
            $table->timestamps();

            $table->unique(['cliente_id', 'periodo_apuracao', 'id_atividade'], 'sra_cliente_periodo_atividade_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simples_receitas_atividades');
    }
};
