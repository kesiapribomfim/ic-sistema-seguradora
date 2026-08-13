<?php

namespace Database\Factories;

use App\Models\Cotacao;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Segurado;
use App\Models\User;
use App\Models\Filial;
use App\Models\Produto;

/**
 * @extends Factory<Cotacao>
 */
class CotacaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->unique()->uuid(),
            //fk
            'segurado_id' => Segurado::factory(),
            'user_id' => User::factory(),
            'filial_id' => Filial::factory(),
            'produto_id' => Produto::factory(),

            //ajustar dados jsonb depois
            'dados_especificos' => [
                'categoria' => $this->faker->randomElement(['Veículo', 'Imóvel Residencial', 'Equipamento Solar', 'Frota Corporativa']),
                'ano_fabricacao' => $this->faker->year(),
                'cep_risco' => $this->faker->postcode(),
                'score_ia_risco' => $this->faker->numberBetween(1, 100),
                'classificacao_esg' => $this->faker->randomElement(['A', 'B', 'C', 'N/A']),
            ],
            'cobertura_selecionada' => [
                [
                    'tipo' => 'Danos Morais e Materiais a Terceiros',
                    'indenizacao_maxima' => 100000.00,
                    'franquia' => 0.00
                ],
                [
                    'tipo' => $this->faker->randomElement(['Roubo e Furto', 'Incêndio', 'Desastres Naturais']),
                    'indenizacao_maxima' => 50000.00,
                    'franquia' => 1500.00
                ]
            ],
            
            'status' => $this->faker->randomElement([
                'Em Elaboração', 
                'Enviada ao Cliente', 
                'Aceita', 
                'Recusada', 
                'Expirada'
            ]),
            'valor_total'=> $this->faker->randomFloat(2,1000,5000),
            'validade'=> $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            
        ];
    }
}
