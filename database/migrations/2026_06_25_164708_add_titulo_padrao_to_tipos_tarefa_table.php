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
        Schema::table('tipos_tarefa', function (Blueprint $table) {
            $table->string('titulo_padrao')->nullable()->after('nome');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_tarefa', function (Blueprint $table) {
            $table->dropColumn('titulo_padrao');
        });
    }
};
