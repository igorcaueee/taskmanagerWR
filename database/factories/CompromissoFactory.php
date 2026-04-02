<?php

namespace Database\Factories;

use App\Models\Compromisso;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compromisso>
 */
class CompromissoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];

        return [
            'titulo' => fake()->sentence(3),
            'descricao' => fake()->optional()->paragraph(),
            'data' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'hora' => fake()->optional()->time('H:i'),
            'cor' => fake()->randomElement($colors),
            'criado_por' => Usuario::query()->inRandomOrder()->value('id') ?? 1,
        ];
    }
}
