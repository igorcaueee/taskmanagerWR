<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_campanhas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('assunto');
            $table->longText('conteudo_html');
            $table->enum('status', ['rascunho', 'enviando', 'enviada'])->default('rascunho');
            $table->json('destinatarios')->nullable()->comment('Array de IDs de clientes selecionados');
            $table->integer('total_destinatarios')->default(0);
            $table->integer('total_enviados')->default(0);
            $table->timestamp('enviada_em')->nullable();
            $table->foreignId('criado_por')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_campanhas');
    }
};
