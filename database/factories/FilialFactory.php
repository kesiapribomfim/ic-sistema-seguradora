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
            'telefone' => $this->faker->cellphoneNumber(false),
            'endereco' => $this->faker->streetAddress(),
            'bairro' => $this->faker->citySuffix(),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->stateAbbr(),
            'cep' => $this->faker->numerify('########'),
        ];
    }
}
