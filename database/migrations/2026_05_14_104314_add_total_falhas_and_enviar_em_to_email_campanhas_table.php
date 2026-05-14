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
        Schema::table('email_campanhas', function (Blueprint $table) {
            $table->integer('total_falhas')->default(0)->after('total_enviados');
            $table->timestamp('enviar_em')->nullable()->after('enviada_em');
            $table->enum('status', ['rascunho', 'agendada', 'enviando', 'enviada', 'falha_parcial'])->default('rascunho')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_campanhas', function (Blueprint $table) {
            $table->dropColumn('total_falhas');
            $table->dropColumn('enviar_em');
            $table->enum('status', ['rascunho', 'enviando', 'enviada'])->default('rascunho')->change();
        });
    }
};
