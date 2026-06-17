<?php

namespace Database\Factories;

use App\Models\SeguradoPf;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeguradoPf>
 */
class SeguradoPfFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cpf' => $this->faker->numerify('###########'),
            'rg' => $this->faker->numerify('MG-##.###.###'),
            'nome' =>$this->faker->name(),
            'data_nascimento' => $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'profissao'=>$this->faker->jobTitle(),
        ];
    }
}
