<?php

namespace Database\Seeders;

use App\Models\Etapa;
use Illuminate\Database\Seeder;

class popularEtapas extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $etapas = [
            ['nome' => 'A Fazer', 'ordem' => 1, 'cor' => '#6b7280'],
            ['nome' => 'Andamento', 'ordem' => 2, 'cor' => '#3b82f6'],
            ['nome' => 'Impedimento', 'ordem' => 3, 'cor' => '#ef4444'],
            ['nome' => 'Finalizado', 'ordem' => 5, 'cor' => '#22c55e'],
        ];

        foreach ($etapas as $etapa) {
            Etapa::firstOrCreate(['nome' => $etapa['nome']], $etapa);
        }
    }
}
