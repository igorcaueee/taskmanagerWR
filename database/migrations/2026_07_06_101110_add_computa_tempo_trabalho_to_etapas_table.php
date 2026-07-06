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
            $table->boolean('computa_tempo_trabalho')->default(false)->after('visivel');
        });

        DB::table('etapas')->where('nome', 'Andamento')->update(['computa_tempo_trabalho' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etapas', function (Blueprint $table) {
            $table->dropColumn('computa_tempo_trabalho');
        });
    }
};
