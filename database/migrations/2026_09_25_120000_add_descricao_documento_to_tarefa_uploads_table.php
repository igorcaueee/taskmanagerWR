<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->string('descricao_documento')->nullable()->after('tipo_arquivo');
        });
    }

    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropColumn('descricao_documento');
        });
    }
};
