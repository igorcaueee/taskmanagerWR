<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->timestamps();

            $table->unique('data_inicio');
            $table->index('data_inicio');
            $table->index('data_fim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ciclos');
    }
};
