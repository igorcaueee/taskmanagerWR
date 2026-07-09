<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagem_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensagem_id')->constrained('mensagens')->cascadeOnDelete();
            $table->string('caminho');
            $table->string('nome_original');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('tamanho')->nullable();
            $table->enum('tipo', ['imagem', 'arquivo'])->default('arquivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagem_anexos');
    }
};
