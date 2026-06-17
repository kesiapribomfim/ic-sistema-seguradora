<?php

namespace Database\Factories;

use App\Models\SeguradoPj;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeguradoPj>
 */
class SeguradoPjFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cnpj'=> $this->faker->cnpj(false),
            'razao_social' =>$this->faker->company(),
            'inscricao_estadual'=> $this ->faker->numerify('##############'),
        ];
    }
}
