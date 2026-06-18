<?php

namespace Database\Factories;

use App\Models\Apolice;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Segurado;
use App\Models\User;
use App\Models\Filial;
use App\Models\Cotacao;


/**
 * @extends Factory<Apolice>
 */
class ApoliceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //fk
            'segurado_id' => Segurado::factory(),
            'user_id' => User::factory(),
            'filial_id' => Filial::factory(),
            'cotacao'=> Cotacao::factory(),

            'numero_apolice' => $this->faker->numerify,
            
        ];
    }
}
