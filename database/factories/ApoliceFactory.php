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
        
        $dataInicio = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $valorTotal = $this->faker->randomFloat(2,10000,50000); //repensar direitinho
        $quantidadeParcelas = $this->faker->numberBetween(1,48);

        return [
            //fk
            'segurado_id' => Segurado::factory(),
            'user_id' => User::factory(),
            'filial_id' => Filial::factory(),
            'cotacao_id'=> Cotacao::factory(),
            'apolice_origem_id' => null, //padrão nova apolice

            'numero_apolice' => $this->faker->numerify('AP-########'),
            'data_emissao' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'data_inicio' => $dataInicio->format('Y-m-d'),
            'data_fim' => $this->faker->dateTimeBetween($dataInicio, '+1 year')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['Vigente', 'Cancelada', 'Suspensa por inadimplência', 'Renovada', 'Expirada']),
            'snapshot' => [
                
            ],
            'dados_bem_assegurado' =>[

            ],
            'beneficiarios' =>$this->faker->optional()->name(),
            'forma_pagamento' =>$this->faker->creditCardType(),
            'quantidade_parcelas' => $quantidadeParcelas,
            'valor_parcela' => $valorTotal/$quantidadeParcelas,
            'valor_total'=> $valorTotal
        ];
    }
}
