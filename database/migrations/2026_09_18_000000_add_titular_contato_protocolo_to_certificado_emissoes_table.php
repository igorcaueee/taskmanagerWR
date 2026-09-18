<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificado_emissoes', function (Blueprint $table) {
            $table->string('titular_nome')->nullable()->after('cliente_documento');
            $table->string('telefone')->nullable()->after('titular_nome');
            $table->string('email')->nullable()->after('telefone');
            $table->string('protocolo')->nullable()->after('certificadora');
            $table->string('status_ar')->nullable()->after('protocolo');
        });
    }

    public function down(): void
    {
        Schema::table('certificado_emissoes', function (Blueprint $table) {
            $table->dropColumn(['titular_nome', 'telefone', 'email', 'protocolo', 'status_ar']);
        });
    }
};
