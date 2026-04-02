<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('etapas')->insertOrIgnore([
            'nome'       => 'Transferido para o próximo ciclo',
            'ordem'      => 99,
            'cor'        => '#f59e0b',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('etapas')->where('nome', 'Transferido para o próximo ciclo')->delete();
    }
};
