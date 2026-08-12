<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de cada evento de fechamento do EFD-Reinf (R-2099 fecha a série
 * R-1000/R-2000; R-4099 fecha a série R-4000) enviado via ReinfEnvioService.
 * Um lote assíncrono da Receita = um único evento aqui (ver ReinfService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reinf_fechamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('tipo_evento', ['R-2099', 'R-4099']);
            $table->string('periodo_apuracao', 7); // AAAA-MM
            $table->enum('ambiente', ['homologacao', 'producao']);
            $table->enum('status', ['rascunho', 'enviado', 'processando', 'sucesso', 'erro'])
                ->default('rascunho');

            $table->string('id_evento', 36)->nullable();
            $table->string('numero_protocolo', 49)->nullable();
            $table->string('numero_recibo', 52)->nullable();
            $table->unsignedTinyInteger('cd_resposta')->nullable();

            $table->longText('xml_evento')->nullable();
            $table->longText('xml_retorno')->nullable();
            $table->text('erro_mensagem')->nullable();

            $table->timestamps();

            $table->unique(['cliente_id', 'tipo_evento', 'periodo_apuracao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reinf_fechamentos');
    }
};
