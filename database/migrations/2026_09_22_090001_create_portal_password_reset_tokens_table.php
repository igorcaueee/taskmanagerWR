<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesmo esquema de `password_reset_tokens` (ver 0001_01_01_000000_create_users_table.php),
 * só que separado para o guard `portal` — os e-mails de PortalUsuario não são únicos
 * globalmente como os de `users`, então esse fluxo é isolado por completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_password_reset_tokens');
    }
};
