<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nome'              => $this->faker->company(),
            'cpfcnpj'           => $this->faker->numerify('##.###.###/####-##'),
            'tipo'              => 1, // 1=PJ, 0=PF
            'regime_tributario' => $this->faker->randomElement(['simples_nacional', 'lucro_presumido', 'lucro_real']),
            'status'            => 'ativo',
        ];
    }

    public function conectadoContaAzul(): static
    {
        return $this->state([
            'conta_azul_conectada'       => true,
            'conta_azul_access_token'    => 'fake-access-token',
            'conta_azul_refresh_token'   => 'fake-refresh-token',
            'conta_azul_token_expira_em' => now()->addHour(),
        ]);
    }

    public function tokenExpirado(): static
    {
        return $this->state([
            'conta_azul_conectada'       => true,
            'conta_azul_access_token'    => 'expired-access-token',
            'conta_azul_refresh_token'   => 'fake-refresh-token',
            'conta_azul_token_expira_em' => now()->subMinute(),
        ]);
    }
}
