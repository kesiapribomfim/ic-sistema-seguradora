<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
Use App\Models\Produto;
Use App\Models\Cobertura;

class ProdutoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $produtos = [
            [
                'nome' => 'Auto Completo',
                'codigo' => 'AUTO-COMP-01',
                'ramo' => 'Auto',
                'descricao' => 'Proteção completa para o seu veículo com franquia reduzida.',
                'status' => true,
                'versao' => 'v1.0',
                'parametros_calculo' => ['taxa_base' => 0.05],
            ],
            [
                'nome' => 'Auto Econômico',
                'codigo' => 'AUTO-ECO-01',
                'ramo' => 'Auto',
                'descricao' => 'Proteção essencial para quem busca economia.',
                'status' => true,
                'versao' => 'v1.0',
                'parametros_calculo' => ['taxa_base' => 0.03],
            ],
            [
                'nome' => 'Residencial Premium',
                'codigo' => 'RES-PREM-01',
                'ramo' => 'Residencial',
                'descricao' => 'Seguro completo para casas e apartamentos.',
                'status' => true,
                'versao' => 'v1.0',
                'parametros_calculo' => ['taxa_base' => 0.002],
            ],
            [
                'nome' => 'Vida Tranquila',
                'codigo' => 'VIDA-TRANQ-01',
                'ramo' => 'Vida',
                'descricao' => 'Garantia de tranquilidade para sua família.',
                'status' => true,
                'versao' => 'v1.0',
                'parametros_calculo' => ['taxa_base' => 0.015],
            ],
        ];

        foreach($produtos as $dados) {
            $produto = Produto::create($dados);

            $coberturasDoRamo = Cobertura::where('ramo', $produto->ramo)->get();

            foreach($coberturasDoRamo as $cobertura) {
                $produto->coberturas()->attach($cobertura->id, [
                    'limite_maximo' => rand(10,150) * 1000
                ]);
            }

        }
    }
}
