<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log de alterações de st_regras_cest pela tela "Regras cadastradas" —
     * uma linha por campo alterado em cada edição (valor antigo → novo, quem,
     * quando). st_regras_cest.editado_por/editado_em continuam guardando só
     * a última edição (consulta rápida); esta tabela guarda o histórico
     * completo.
     */
    public function up(): void
    {
        Schema::create('st_regras_cest_historico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('st_regra_cest_id')->constrained('st_regras_cest')->cascadeOnDelete();
            $table->string('campo');
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->string('editado_por')->nullable();
            $table->dateTime('editado_em');
            $table->timestamps();

            $table->index(['st_regra_cest_id', 'editado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_regras_cest_historico');
    }
};
