<?php

namespace Database\Factories;

use App\Models\EtapaFunil;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'email' => $this->faker->optional()->safeEmail(),
            'telefone' => $this->faker->optional()->phoneNumber(),
            'empresa' => $this->faker->optional()->company(),
            'faturamento' => $this->faker->optional()->randomFloat(2, 10000, 500000),
            'servico' => $this->faker->optional()->words(3, true),
            'honorario' => $this->faker->optional()->randomFloat(2, 500, 10000),
            'possibilidade' => $this->faker->optional()->sentence(),
            'etapa_funil_id' => EtapaFunil::inRandomOrder()->first()?->id ?? 1,
            'responsavel_id' => null,
            'origem' => $this->faker->randomElement(['manual', 'formulario']),
            'observacoes' => $this->faker->optional()->sentence(),
            'convertido_cliente_id' => null,
        ];
    }
}
