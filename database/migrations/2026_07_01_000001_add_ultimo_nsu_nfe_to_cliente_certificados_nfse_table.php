<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_certificados_nfse', function (Blueprint $table) {
            $table->bigInteger('ultimo_nsu_nfe')->default(0)->after('ultimo_nsu');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_certificados_nfse', function (Blueprint $table) {
            $table->dropColumn('ultimo_nsu_nfe');
        });
    }
};
