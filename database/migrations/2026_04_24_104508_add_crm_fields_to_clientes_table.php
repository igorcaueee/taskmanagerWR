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
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('faturamento', 15, 2)->nullable()->after('descricao');
            $table->string('servico')->nullable()->after('faturamento');
            $table->decimal('honorario', 15, 2)->nullable()->after('servico');
            $table->text('possibilidade')->nullable()->after('honorario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['faturamento', 'servico', 'honorario', 'possibilidade']);
        });
    }
};
