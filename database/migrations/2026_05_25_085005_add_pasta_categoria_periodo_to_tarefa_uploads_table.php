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
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->string('pasta_categoria')->nullable()->after('arquivo_path');
            $table->string('pasta_periodo')->nullable()->after('pasta_categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropColumn(['pasta_categoria', 'pasta_periodo']);
        });
    }
};
