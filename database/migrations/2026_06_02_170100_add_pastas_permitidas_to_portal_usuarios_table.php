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
        Schema::table('portal_usuarios', function (Blueprint $table) {
            $table->boolean('acesso_total')->default(true)->after('ativo');
            $table->json('pastas_permitidas')->nullable()->after('acesso_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_usuarios', function (Blueprint $table) {
            $table->dropColumn(['acesso_total', 'pastas_permitidas']);
        });
    }
};
