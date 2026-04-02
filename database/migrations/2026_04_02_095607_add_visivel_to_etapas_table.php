<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            $table->boolean('visivel')->default(true)->after('cor');
        });

        // Mark the "Transferido" etapa as invisible
        DB::table('etapas')
            ->where('nome', 'Transferido para o próximo ciclo')
            ->update(['visivel' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            $table->dropColumn('visivel');
        });
    }
};
