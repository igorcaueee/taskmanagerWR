<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dados fiscais do cliente necessários para ele atuar como prestador na emissão
 * de NFS-e pelo Sistema Nacional NFS-e (DPS/infDPS/prest) — inscrição municipal,
 * endereço estruturado e código IBGE do município, que não existem em `clientes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_dados_fiscais_nfse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->cascadeOnDelete();
            $table->string('inscricao_municipal')->nullable();
            $table->string('codigo_municipio_ibge', 7)->nullable();
            $table->string('cep', 8)->nullable();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->unsignedInteger('proximo_numero_dps')->default(1);
            $table->string('serie_dps')->default('1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_dados_fiscais_nfse');
    }
};
