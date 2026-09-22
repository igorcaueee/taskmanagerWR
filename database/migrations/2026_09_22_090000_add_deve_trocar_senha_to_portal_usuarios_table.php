<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_usuarios', function (Blueprint $table) {
            $table->boolean('deve_trocar_senha')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('portal_usuarios', function (Blueprint $table) {
            $table->dropColumn('deve_trocar_senha');
        });
    }
};
