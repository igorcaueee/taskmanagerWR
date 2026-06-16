<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->enum('tipo_arquivo', ['pagamento', 'contrato_social', 'informacao'])->nullable()->after('pasta_periodo');
            $table->date('data_vencimento')->nullable()->after('tipo_arquivo');
            $table->decimal('valor', 10, 2)->nullable()->after('data_vencimento');
            $table->timestamp('pago_em')->nullable()->after('valor');
            $table->foreignId('pago_por')->nullable()->constrained('portal_usuarios')->nullOnDelete()->after('pago_em');
        });
    }

    public function down(): void
    {
        Schema::table('tarefa_uploads', function (Blueprint $table) {
            $table->dropForeign(['pago_por']);
            $table->dropColumn(['tipo_arquivo', 'data_vencimento', 'valor', 'pago_em', 'pago_por']);
        });
    }
};
