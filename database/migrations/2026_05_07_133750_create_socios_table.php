<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->unsignedTinyInteger('ordem')->default(1);
            $table->string('cpfcnpj')->nullable();
            $table->string('nome');
            $table->decimal('participacao', 8, 4)->default(0);
            $table->decimal('quotas_integralizadas', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
