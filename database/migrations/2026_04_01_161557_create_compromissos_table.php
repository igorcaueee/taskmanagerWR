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
        Schema::create('compromissos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->date('data');
            $table->time('hora')->nullable();
            $table->string('cor', 7)->default('#3b82f6');
            $table->foreignId('criado_por')->constrained('usuarios')->cascadeOnDelete();
            $table->timestamps();

            $table->index('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compromissos');
    }
};
