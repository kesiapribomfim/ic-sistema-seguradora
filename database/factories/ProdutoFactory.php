<?php

namespace Database\Factories;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->word(),
            'codigo' => $this->faker->unique()->numerify('PROD-###'),
            'ramo' => $this->faker->randomElement(['Auto', 'Residencial', 'Vida']),
            'descricao' => $this->faker->sentence(),
            'lista_resumida' => $this->faker->sentence(),
            'status' => $this->faker->boolean(),
            'versao' => $this->faker->numerify('v#.##'),
            'coberturas' => [
                ['nome' => 'Cobertura A', 'valor' => $this->faker->randomFloat(2, 1000, 5000)],
                ['nome' => 'Cobertura B', 'valor' => $this->faker->randomFloat(2, 500, 3000)],
            ],
            'parametros_calculo' => [
                ['parametro' => 'Idade', 'valor' => $this->faker->numberBetween(18, 70)],
                ['parametro' => 'Valor do Bem', 'valor' => $this->faker->randomFloat(2, 10000, 100000)],
            ],
        ];
    }
}
