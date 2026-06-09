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
        Schema::create('questionario_resposta_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resposta_id')->constrained('questionario_respostas')->cascadeOnDelete();
            $table->foreignId('pergunta_id')->constrained('questionario_perguntas');
            $table->foreignId('opcao_id')->constrained('questionario_opcoes');
            $table->unsignedTinyInteger('pontos');
            $table->timestamps();
            $table->unique(['resposta_id', 'pergunta_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionario_resposta_itens');
    }
};
