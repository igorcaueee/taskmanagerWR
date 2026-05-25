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
        Schema::create('tarefa_upload_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarefa_upload_id')->constrained('tarefa_uploads')->cascadeOnDelete();
            $table->foreignId('portal_usuario_id')->constrained('portal_usuarios')->cascadeOnDelete();
            $table->enum('tipo', ['visualizou', 'baixou']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarefa_upload_eventos');
    }
};
