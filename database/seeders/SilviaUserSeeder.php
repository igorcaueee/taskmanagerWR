<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SilviaUserSeeder extends Seeder
{
    public function run(): void
    {
        $recepcao = Departamento::where('nome', 'Recepção')->first();

        $silvia = Usuario::firstOrCreate(
            ['email' => 'silvia@assessoriawr.com'],
            [
                'nome' => 'Silvia',
                'senha' => Hash::make('Silvia@2026'),
                'cargo' => 'analista',
                'status' => true,
                'data_registro' => now()->toDateString(),
                'departamento_id' => $recepcao?->id,
            ]
        );

        if ($recepcao && ! $silvia->departamento_id) {
            $silvia->update(['departamento_id' => $recepcao->id]);
        }
    }
}
