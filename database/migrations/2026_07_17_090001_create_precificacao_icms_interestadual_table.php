<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precificacao_icms_interestadual', function (Blueprint $table) {
            $table->id();
            $table->string('uf_origem', 2);
            $table->string('uf_destino', 2);
            $table->decimal('aliquota', 5, 2);
            $table->timestamps();

            $table->unique(['uf_origem', 'uf_destino']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precificacao_icms_interestadual');
    }
};
