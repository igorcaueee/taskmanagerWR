<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de referência estática (Lista Nacional de Serviços / LC 116, Anexo I do
 * Sistema Nacional NFS-e) usada para selecionar o código de tributação nacional
 * (cTribNac) ao emitir uma DPS. Populada via NfseServicosNacionaisSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_servicos_nacionais', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_tributacao_nacional', 6)->unique();
            $table->string('descricao', 600);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_servicos_nacionais');
    }
};
