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
        Schema::table('contato_clientes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('capital_social', 15, 2)->nullable()->after('honorario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contato_clientes', function (Blueprint $table) {
            $table->string('tipo')->nullable()->after('nome');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('capital_social');
        });
    }
};
