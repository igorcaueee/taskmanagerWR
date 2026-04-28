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
        Schema::create('etapas_funil', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedInteger('ordem')->default(0);
            $table->string('cor', 7)->default('#6b7280');
            $table->timestamps();
        });

        $etapas = [
            ['nome' => 'Prospecção/Lead',  'ordem' => 1, 'cor' => '#3B82F6'],
            ['nome' => 'Qualificação',     'ordem' => 2, 'cor' => '#8B5CF6'],
            ['nome' => 'Possibilidades',   'ordem' => 3, 'cor' => '#F59E0B'],
            ['nome' => 'Closer',           'ordem' => 4, 'cor' => '#F97316'],
            ['nome' => 'Cliente',          'ordem' => 5, 'cor' => '#10B981'],
            ['nome' => 'Pós Venda',        'ordem' => 6, 'cor' => '#14B8A6'],
        ];

        foreach ($etapas as $etapa) {
            DB::table('etapas_funil')->insert(array_merge($etapa, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapas_funil');
    }
};
