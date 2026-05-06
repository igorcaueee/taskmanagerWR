<?php

namespace Database\Seeders;

use App\Models\Departamento;
use Illuminate\Database\Seeder;

class DepartamentoRecepcaoSeeder extends Seeder
{
    public function run(): void
    {
        Departamento::firstOrCreate(['nome' => 'Recepção']);
    }
}
