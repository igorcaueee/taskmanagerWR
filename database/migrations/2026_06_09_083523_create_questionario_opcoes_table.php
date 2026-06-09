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
        Schema::create('questionario_opcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pergunta_id')->constrained('questionario_perguntas')->cascadeOnDelete();
            $table->string('texto'); // "Não / Ruim", "Parcial", "Sim / Adequado"
            $table->unsignedTinyInteger('pontos'); // 0, 5, 10
            $table->unsignedTinyInteger('ordem');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionario_opcoes');
    }
};
