<?php

namespace Database\Factories;

use App\Models\ProdutoFinanceiro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProdutoFinanceiro>
 */
class ProdutoFinanceiroFactory extends Factory
{
    protected $model = ProdutoFinanceiro::class;

    public function definition(): array
    {
        return [
            'nome'          => $this->faker->words(3, true),
            'codigo'        => strtoupper($this->faker->bothify('??-###')),
            'categoria'     => $this->faker->word(),
            'preco_custo'   => $this->faker->randomFloat(2, 1, 100),
            'preco_venda'   => $this->faker->randomFloat(2, 10, 500),
            'estoque_atual' => $this->faker->numberBetween(0, 999),
            'ativo'         => true,
        ];
    }
}
