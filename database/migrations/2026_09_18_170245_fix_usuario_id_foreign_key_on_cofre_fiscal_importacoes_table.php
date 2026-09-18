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
        Schema::table('cofre_fiscal_importacoes', function (Blueprint $table) {
            // A FK original apontava pra `users` (model padrão do Laravel), mas o projeto
            // autentica via App\Models\Usuario (tabela `usuarios`, ver config/auth.php) —
            // upload no cofre fiscal quebrava com "foreign key constraint fails" porque o
            // id do usuário logado não existe em `users`.
            $table->dropForeign(['usuario_id']);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cofre_fiscal_importacoes', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
