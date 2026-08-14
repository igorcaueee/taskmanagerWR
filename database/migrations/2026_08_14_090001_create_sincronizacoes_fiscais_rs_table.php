<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sincronizacoes_fiscais_rs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('fase');
            $table->string('status');
            $table->text('mensagem_erro')->nullable();
            $table->dateTime('executado_em');
            $table->timestamps();

            $table->index(['cliente_id', 'fase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sincronizacoes_fiscais_rs');
    }
};
