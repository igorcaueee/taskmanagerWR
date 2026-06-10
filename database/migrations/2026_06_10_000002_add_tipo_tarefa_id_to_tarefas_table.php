<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->foreignId('tipo_tarefa_id')->nullable()->after('id')->constrained('tipos_tarefa')->nullOnDelete();
            $table->index('tipo_tarefa_id');
        });
    }

    public function down(): void
    {
        Schema::table('tarefas', function (Blueprint $table) {
            $table->dropForeign(['tipo_tarefa_id']);
            $table->dropIndex(['tipo_tarefa_id']);
            $table->dropColumn('tipo_tarefa_id');
        });
    }
};
