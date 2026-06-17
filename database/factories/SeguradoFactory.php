<?php

namespace Database\Factories;

use App\Models\Segurado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Segurado>
 */
class SeguradoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => $this->faker->randomElement(['CPF', 'CNPJ']),
            'telefone' => $this->faker->cellphoneNumber(false),
            'email' => $this->faker->unique()->safeEmail(),
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->citySuffix(),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->stateAbbr(),
            'cep' => $this->faker->numerify('########'),
            'score' => $this->faker->numberBetween(0, 1000),
            'status' => $this->faker->boolean(80), // 80% chance of being true (Ativo)
        ];
    }
}
