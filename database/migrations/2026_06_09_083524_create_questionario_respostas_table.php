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
        Schema::create('questionario_respostas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionario_id')->constrained('questionarios')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('respondente_nome');
            $table->string('respondente_email')->nullable();
            $table->string('respondente_empresa')->nullable();
            $table->string('respondente_segmento')->nullable();
            $table->decimal('faturamento_mensal', 15, 2)->nullable();
            $table->unsignedInteger('num_colaboradores')->nullable();
            $table->decimal('pontuacao_total', 5, 2)->nullable();
            $table->string('classificacao')->nullable(); // ide range label
            $table->string('token')->unique(); // para retomar sessão
            $table->boolean('finalizado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questionario_respostas');
    }
};
