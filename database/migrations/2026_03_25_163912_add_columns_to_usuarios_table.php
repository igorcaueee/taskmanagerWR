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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('telefone')->nullable()->after('cargo');
            $table->string('sexo')->nullable()->after('telefone');
            $table->date('data_nascimento')->nullable()->after('sexo');
            $table->date('data_registro')->nullable()->after('data_nascimento');
            $table->boolean('status')->default(true)->after('data_registro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['telefone', 'sexo', 'data_nascimento', 'data_registro', 'status']);
        });
    }
};
