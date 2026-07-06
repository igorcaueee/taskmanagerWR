<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Certificado digital da própria contabilidade — usado no webservice de
 * download de XML para contabilistas da SEFAZ-RS (NFeIntegracao), diferente
 * do certificado por cliente usado na Distribuição DFe nacional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados_contabilidade', function (Blueprint $table) {
            $table->id();
            $table->string('arquivo'); // path relativo ao storage/app
            $table->text('senha');    // criptografado via cast
            $table->enum('ambiente', ['homologacao', 'producao'])->default('producao');
            $table->date('vencimento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_contabilidade');
    }
};
