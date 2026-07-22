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
            'status' => $this->faker->boolean(),
            'versao' => $this->faker->numerify('v#.##'),
            'parametros_calculo' => [
                'taxa_base' => $this->faker->randomFloat(2, 0.01, 0.05),
                'desconto_maximo' => $this->faker->randomFloat(2, 5, 15),
            ],
        ];
    }
}
