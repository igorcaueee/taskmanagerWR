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
        Schema::table('questionario_respostas', function (Blueprint $table) {
            $table->string('respondente_telefone')->nullable()->after('respondente_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionario_respostas', function (Blueprint $table) {
            $table->dropColumn('respondente_telefone');
        });
    }
};
