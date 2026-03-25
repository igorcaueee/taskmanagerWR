<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'igorwagner2003@gmail.com';

        Usuario::updateOrCreate(
            ['email' => $email],
            [
                'nome' => 'Igor Caue',
                // store hashed password in the 'senha' column
                'senha' => Hash::make('ig150418'),
                'cargo' => 'diretor',
            ]
        );
    }
}
