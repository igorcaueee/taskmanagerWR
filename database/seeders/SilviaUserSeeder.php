<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SilviaUserSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::firstOrCreate(
            ['email' => 'silvia@wrass.com.br'],
            [
                'nome' => 'Silvia',
                'senha' => Hash::make('Silvia@2026'),
                'cargo' => 'analista',
                'status' => true,
                'data_registro' => now()->toDateString(),
            ]
        );
    }
}
