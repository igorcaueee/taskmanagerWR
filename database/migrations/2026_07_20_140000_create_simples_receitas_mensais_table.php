<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receita bruta do mês corrente por cliente, lançada manualmente pelo escritório
 * — a SERPRO não tem como fornecer o faturamento do período atual, só o
 * histórico já transmitido. É esse valor que alimenta a transmissão do PGDASD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simples_receitas_mensais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('periodo_apuracao', 6); // YYYYMM
            $table->decimal('receita_bruta_competencia', 15, 2)->default(0);
            $table->decimal('receita_bruta_caixa', 15, 2)->nullable();
            $table->enum('regime_apuracao', ['competencia', 'caixa'])->default('competencia');
            $table->timestamps();

            $table->unique(['cliente_id', 'periodo_apuracao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simples_receitas_mensais');
    }
};
