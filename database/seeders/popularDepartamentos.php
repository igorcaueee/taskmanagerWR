<?php

namespace Database\Seeders;

use App\Models\Departamento;
use Illuminate\Database\Seeder;

class popularDepartamentos extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departamentos = [
            'RH/DP',
            'Registro de Empresas',
            'Fiscal',
            'Contábil',
            'Financeiro',
            'Administrativo',
        ];

        foreach ($departamentos as $nome) {
            Departamento::firstOrCreate(['nome' => $nome]);
        }
    }
}
