<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de emissões de certificados digitais para clientes (e-CNPJ / e-CPF),
 * substitui a planilha manual. "Cliente WR" é derivado de cliente_id preenchido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificado_emissoes', function (Blueprint $table) {
            $table->id();
            $table->date('data_emissao');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('cliente_nome');
            $table->string('cliente_documento')->nullable();
            $table->string('modelo')->default('ECNPJ'); // ECNPJ | ECPF
            $table->string('numero_pedido')->nullable();
            $table->string('forma_emissao')->default('PRESENCIAL'); // PRESENCIAL | VIDEO
            $table->decimal('valor', 10, 2)->nullable();
            $table->string('pagamento')->nullable(); // PIX | DINHEIRO | BONIFICADO | ...
            $table->string('situacao')->default('OK');
            $table->string('certificadora')->default('SOLUCAOID');
            $table->date('vencimento')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('data_emissao');
            $table->index('vencimento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificado_emissoes');
    }
};
