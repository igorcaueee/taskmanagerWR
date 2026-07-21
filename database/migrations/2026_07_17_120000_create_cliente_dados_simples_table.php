<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache local dos dados fiscais do Simples Nacional (CNAE, anexo, RBT12) obtidos
 * via API Integra Contador (SERPRO) — evita reconsultar a API a cada apuração,
 * já que o consumo é cobrado por chamada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_dados_simples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->string('cnae_principal')->nullable();
            $table->string('anexo_simples')->nullable();
            $table->decimal('rbt12', 15, 2)->nullable();
            $table->timestamp('dados_atualizados_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_dados_simples');
    }
};
