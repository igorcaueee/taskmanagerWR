<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simples_das_processamentos', function (Blueprint $table) {
            $table->tinyInteger('tipo_declaracao')->default(1)->after('status'); // 1=Original, 2=Retificadora
        });
    }

    public function down(): void
    {
        Schema::table('simples_das_processamentos', function (Blueprint $table) {
            $table->dropColumn('tipo_declaracao');
        });
    }
};
