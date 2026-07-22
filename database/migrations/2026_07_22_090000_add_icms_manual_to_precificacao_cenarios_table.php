<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('precificacao_cenarios', function (Blueprint $table) {
            $table->enum('tipo_icms_compra', ['st', 'normal'])->default('normal')->after('uf_venda');
            $table->decimal('aliquota_icms_compra_pct', 5, 2)->default(0)->after('tipo_icms_compra');
            $table->boolean('compra_internacional')->default(false)->after('aliquota_icms_compra_pct');
        });
    }

    public function down(): void
    {
        Schema::table('precificacao_cenarios', function (Blueprint $table) {
            $table->dropColumn(['tipo_icms_compra', 'aliquota_icms_compra_pct', 'compra_internacional']);
        });
    }
};
