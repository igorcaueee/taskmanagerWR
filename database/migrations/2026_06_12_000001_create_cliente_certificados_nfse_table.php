<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_certificados_nfse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->string('arquivo'); // path relativo ao storage/app
            $table->text('senha');    // criptografado via cast
            $table->enum('ambiente', ['homologacao', 'producao'])->default('producao');
            $table->bigInteger('ultimo_nsu')->default(0);
            $table->date('vencimento')->nullable();
            $table->timestamps();

            $table->unique('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_certificados_nfse');
    }
};
