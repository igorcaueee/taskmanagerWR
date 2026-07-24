<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sócios de uma DEFIS — array obrigatório dentro de "empresa" no payload da
 * TRANSDECLARACAO141 (um cliente pode ter vários sócios ao mesmo tempo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defis_socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defis_declaracao_id');
            $table->foreign('defis_declaracao_id', 'defis_socios_declaracao_id_foreign')
                ->references('id')->on('defis_declaracoes')
                ->cascadeOnDelete();

            $table->string('cpf', 11);
            $table->decimal('rendimentos_isentos', 15, 2)->default(0);
            $table->decimal('rendimentos_tributaveis', 15, 2)->default(0);
            $table->decimal('participacao_capital_social', 5, 2)->default(0);
            $table->decimal('ir_retido_fonte', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defis_socios');
    }
};
