<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_tarefa', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('titulo_padrao')->nullable()->comment('título pré-definido para preencher automaticamente ao criar tarefa');
            $table->text('descricao')->nullable();
            $table->date('data_vencimento')->nullable()->comment('data pré-definida para preencher automaticamente ao criar tarefa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_tarefa');
    }
};
