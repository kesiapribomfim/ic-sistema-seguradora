<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produto;
use App\Models\Cobertura;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            // ==========================================
            // RAMO: AUTO
            // ==========================================
            [
                'nome' => 'Auto Completo Premium',
                'codigo' => 'AUTO-COMP-01',
                'ramo' => 'Auto',
                'descricao' => 'Proteção completa para o seu veículo com franquia reduzida e serviços 24h.',
                'status' => true,
                'versao' => 'v1.0',
                'valor_alcada' => 150000, // Cotações acima de 150 mil vão para subscrição
                'valor_alcada_aprovacao' => 15000, // Sinistros acima de 15 mil exigem Gestor
                'parametros_calculo' => [
                    'taxa_base' => 5.0, // 5% do valor do veículo
                    'valor_franquia' => 2500,
                    'fator_veiculo_antigo' => 15.0, // +15% no prêmio
                    'fator_tipo_moto' => 30.0,
                    'fator_tipo_caminhao' => 25.0,
                    'fator_kit_gas' => 10.0,
                    'fator_blindado' => 20.0,
                    'fator_uso_comercial' => 25.0, // Uber/Entregas
                    'fator_estacionamento_rua' => 10.0,
                    'fator_sinistro_anterior' => 15.0,
                    'desconto_zero_km' => 10.0, // -10% no prêmio
                    'desconto_garagem' => 8.0,
                    'desconto_por_classe_bonus' => 5.0, // 5% por classe
                ],
            ],
            // ==========================================
            // RAMO: RESIDENCIAL
            // ==========================================
            [
                'nome' => 'Residencial Tranquilidade',
                'codigo' => 'RES-TRANQ-01',
                'ramo' => 'Residencial',
                'descricao' => 'Seguro completo para casas e apartamentos contra incêndio, roubo e danos elétricos.',
                'status' => true,
                'versao' => 'v1.0',
                'valor_alcada' => 2000000,
                'valor_alcada_aprovacao' => 50000,
                'parametros_calculo' => [
                    'taxa_base' => 0.15, // 0.15% do valor do imóvel
                    'valor_franquia' => 1000,
                    'fator_construcao_madeira' => 35.0, // Alto risco
                    'fator_uso_veraneio' => 20.0,
                    'fator_imovel_desocupado' => 40.0,
                    'fator_regiao_rural' => 15.0,
                    'fator_agro_comercial' => 25.0,
                    'fator_terreno_baldio' => 10.0,
                    'fator_sinistro_anterior' => 15.0,
                    'desconto_apartamento' => 20.0, // Muito mais seguro que casa
                    'desconto_condominio_horizontal' => 15.0,
                ],
            ],
            // ==========================================
            // RAMO: VIDA
            // ==========================================
            [
                'nome' => 'Vida Mais Segura',
                'codigo' => 'VIDA-MAIS-01',
                'ramo' => 'Vida',
                'descricao' => 'Garantia de tranquilidade para sua família com ampla cobertura para doenças graves.',
                'status' => true,
                'versao' => 'v1.0',
                'valor_alcada' => 1000000,
                'valor_alcada_aprovacao' => 20000,
                'parametros_calculo' => [
                    'taxa_base' => 0.5,
                    'valor_franquia' => 0,
                    'carencia_dias' => 90,
                    'fator_profissao_risco' => 30.0,
                    'fator_imc_fora_padrao' => 15.0,
                    'fator_doenca_preexistente' => 20.0,
                    'fator_doenca_grave' => 50.0, // Risco altíssimo
                    'fator_fumante' => 25.0,
                    'fator_alcool' => 10.0,
                    'fator_esportes_radicais' => 35.0,
                    'desconto_perfil_saudavel' => 15.0, // -15% se for super saudável
                ],
            ],
        ];

        foreach ($produtos as $dados) {
            $produto = Produto::create($dados);

            $coberturasDoRamo = Cobertura::where('ramo', $produto->ramo)->get();

            if ($coberturasDoRamo->isNotEmpty()) {
                foreach ($coberturasDoRamo as $cobertura) {
                    $produto->coberturas()->attach($cobertura->id, [
                        'limite_maximo' => fake()->numberBetween(10, 150) * 1000,
                        'obrigatoria' => fake()->boolean()
                    ]);
                }
            } else {
                $this->command->warn("Nenhuma cobertura encontrada para o ramo {$produto->ramo}. Você rodou a CoberturaSeeder?");
            }
        }
    }
}
