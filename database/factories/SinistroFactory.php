<?php

namespace Database\Factories;

use App\Models\Apolice;
use App\Models\Sinistro;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $status = $this->faker->randomElement([Sinistro::STATUS_ABERTO, Sinistro::STATUS_ANALISE, Sinistro::STATUS_PERICIA, Sinistro::STATUS_APROVADO, Sinistro::STATUS_NEGADO, Sinistro::STATUS_PAGO, Sinistro::STATUS_ENCERRADO, Sinistro::STATUS_GESTOR]);

        $valorIndenizacao = $this->faker->randomFloat(2, 2000, 50000);

        $valorPago = 0; // Por padrão, começa em zero

        if (in_array($status, [Sinistro::STATUS_PAGO, Sinistro::STATUS_ENCERRADO])) {
            $valorPago = $valorIndenizacao;
        } elseif ($status === Sinistro::STATUS_APROVADO) {
            $valorPago = $this->faker->randomElement([0, ($valorIndenizacao / 2)]);
        }

        return [
            'apolice_id' => Apolice::factory(),

            'data_hora_ocorrencia' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d H:i:s'),

            'rua' => $this->faker->streetName(),
            'numero' => $this->faker->numberBetween(1, 1000),
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
