<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('requer_envio_arquivo');
            $table->unsignedBigInteger('inativado_por')->nullable()->after('ativo');
            $table->timestamp('inativado_em')->nullable()->after('inativado_por');

            $table->foreign('inativado_por')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropForeign(['inativado_por']);
            $table->dropColumn(['ativo', 'inativado_por', 'inativado_em']);
        });
    }
};
