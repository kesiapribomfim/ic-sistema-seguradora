<?php

namespace Database\Factories;

use App\Models\Pagamento;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Apolice;
use App\Models\Sinistro;

/**
 * @extends Factory<Pagamento>
 */
class PagamentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dataVencimento =  $this->faker->dateTimeBetween('-1 month', '+1 month');

        return [
            //fk

            'apolice_id'=>Apolice::factory(),
            'sinistro_id'=>Sinistro::factory(), //inserir possibilidade de ser nulo

            'tipo_movimentacao' => $this->faker->randomElement(['Recebimento', 'Pagamento Indenização']),
            'valor'=> $this->faker->randomFloat(2,10000,50000),
            'num_parcela' => $this->faker->numberBetween(1,48),
            'data_vencimento' => $dataVencimento->format('Y-m-d'),
            'data_pagamento' => $this->faker->dateTimeBetween($dataVencimento, '+1 month'), //pode ser nulo
            'status' => $this->faker->randomElement(['Aberta', 'Paga', 'Vencida', 'Cancelada']), //adicionar condicional bonitinho
            'caminho_fatura_pdf' => $this->faker->url(),
            'metodo_baixa' => $this->faker->randomElement(['Manual','Automática']),





        ];
    }
}
