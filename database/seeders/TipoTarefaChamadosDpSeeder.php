<?php

namespace Database\Seeders;

use App\Models\TipoTarefa;
use Illuminate\Database\Seeder;

class TipoTarefaChamadosDpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TipoTarefa::firstOrCreate(['nome' => 'Admissão']);
        TipoTarefa::firstOrCreate(['nome' => 'Demissão']);
    }
}
