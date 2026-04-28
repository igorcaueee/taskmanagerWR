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
        Schema::create('historico_funil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('etapa_anterior_id')->nullable()->constrained('etapas_funil')->nullOnDelete();
            $table->foreignId('etapa_nova_id')->constrained('etapas_funil')->restrictOnDelete();
            $table->text('descricao')->nullable();
            $table->foreignId('alterado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_funil');
    }
};
