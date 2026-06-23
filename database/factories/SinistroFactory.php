<?php

namespace Database\Factories;

use App\Models\Sinistro;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Apolice;

/**
 * @extends Factory<Sinistro>
 */
class SinistroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = $this->faker->randomElement(['Em análise', 'Em perícia', 'Aprovado', 'Negado', 'Pago', 'Encerrado']);

        $valorIndenizacao = $this->faker->randomFloat(2, 2000, 50000);

        $valorPago = 0; // Por padrão, começa em zero
        
        if (in_array($status, ['Pago', 'Encerrado'])) {
            $valorPago = $valorIndenizacao;
        } elseif ($status === 'Aprovado') {
            $valorPago = $this->faker->randomElement([0, ($valorIndenizacao / 2)]);
        }

        return [
            'apolice_id'=> Apolice::factory(),

            'data_hora_ocorrencia' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),

            'rua' => $this->faker->streetName(),
            'numero' => $this->faker->numberBetween(1,1000),
            'bairro' => $this->faker->citySuffix(),
            'complemento' => $this->faker->optional(0.5)->randomElement(['Sala 1', 'Andar 3', 'Galpão B', 'Térreo']),
            'cidade' => $this->faker->city(),
            'uf' => $this->faker->stateAbbr(),
            'cep' => $this->faker->numerify('########'),

            'descricao' => $this->faker->sentence(),
            'coberturas_envolvidas' => [

            ],
            'status' => $status,
            'valor_indenizacao' => $valorIndenizacao,
            'valor_pago' => $valorPago,
            
        ];
    }
}
