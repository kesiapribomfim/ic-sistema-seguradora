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

            'rua' => $this->faker->streetName(),
            'numero' => $this->faker->numberBetween(1,1000),
            'bairro' => $this->faker->citySuffix(),
            'complemento' => $this->faker->optional(0.5)->randomElement(['Sala 1', 'Andar 3', 'Galpão B', 'Térreo']),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->stateAbbr(),
            'cep' => $this->faker->numerify('########'),
            
            'score' => $this->faker->numberBetween(0, 1000),
            'status' => $this->faker->boolean(80), // 80% chance of being true (Ativo)
        ];
    }
}
