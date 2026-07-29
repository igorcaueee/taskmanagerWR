<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Débitos de uma apuração MIT com movimento — um registro por código de
 * receita lançado (payload "Debitos" da ENCAPURACAO314, agrupado por
 * tributo no envio, ver App\Support\MitCodigosReceita).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mit_debitos_rascunho', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mit_apuracao_rascunho_id');
            $table->foreign('mit_apuracao_rascunho_id', 'mit_debitos_rascunho_apuracao_id_foreign')
                ->references('id')->on('mit_apuracoes_rascunho')
                ->cascadeOnDelete();

            $table->string('grupo', 30);
            $table->string('codigo_receita', 7);
            $table->string('periodicidade', 2);

            $table->unsignedSmallInteger('ano_referencia');
            $table->unsignedTinyInteger('mes_referencia')->nullable();
            $table->unsignedTinyInteger('trimestre_referencia')->nullable();

            $table->decimal('valor', 15, 2);

            // Campos condicionais — só preenchidos quando o código exige
            // (ver App\Support\MitCodigosReceita::exigeEstabelecimento/exigeIncorporacao/exigeScp/exigeMunicipioOuro).
            $table->string('cnpj_estabelecimento', 14)->nullable();
            $table->string('cnpj_incorporacao', 14)->nullable();
            $table->string('cnpj_scp', 14)->nullable();
            $table->string('codigo_municipio_ouro', 7)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mit_debitos_rascunho');
    }
};
