<?php

namespace Database\Factories;

use App\Models\Filial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Filial>
 */
class FilialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->company(),
            'cnpj' => $this->faker->cnpj(false),
            'telefone' => $this->faker->numerify('###########'),
            'rua' => $this->faker->streetName(),
            'numero' => $this->faker->numberBetween(1,1000),
            'bairro' => $this->faker->citySuffix(),
            'complemento' => $this->faker->optional(0.5)->randomElement(['Sala 1', 'Andar 3', 'Galpão B', 'Térreo']),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->stateAbbr(),
            'cep' => $this->faker->numerify('########'),
        ];
    }
}
