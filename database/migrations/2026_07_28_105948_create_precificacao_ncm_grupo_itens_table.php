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
        Schema::create('precificacao_ncm_grupo_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('precificacao_ncm_grupo_id')->constrained('precificacao_ncm_grupos')->cascadeOnDelete();
            $table->string('ncm', 20);
            $table->timestamps();

            $table->unique(['precificacao_ncm_grupo_id', 'ncm'], 'ncm_grupo_itens_grupo_ncm_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('precificacao_ncm_grupo_itens');
    }
};
