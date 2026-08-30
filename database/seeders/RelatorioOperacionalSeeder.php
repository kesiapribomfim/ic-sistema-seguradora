<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apolice;
use App\Models\Sinistro;
use App\Models\Segurado;
use App\Models\User;
use App\Models\Filial;
use App\Models\Cotacao;
use Carbon\Carbon;

class RelatorioOperacionalSeeder extends Seeder
{
    public function run(): void
    {
        $segurado = Segurado::first() ?? Segurado::factory()->create();
        $corretor = User::role('Corretor')->first() ?? User::factory()->create();
        $filial = Filial::first() ?? Filial::factory()->create();

        $cotacao = Cotacao::first();
        if (!$cotacao) {
            $cotacao = Cotacao::create([
                'segurado_id' => $segurado->id,
                'user_id' => $corretor->id,
                'filial_id' => $filial->id,
                'status' => 'Aprovada',
                'valor_estimado' => 3000.00,
            ]);
        }

        $snapshotExemplo = [
            'produto' => 'Seguro Auto Exemplo',
            'coberturas' => [
                ['nome_cobertura' => 'Colisão e Danos Materiais', 'valor' => 50000.00]
            ]
        ];

        $dadosBemExemplo = [
            'marca' => 'Fiat',
            'modelo' => 'Uno',
            'ano' => 2022,
            'placa' => 'ABC-1234'
        ];

        for ($i = 1; $i <= 3; $i++) {
            Apolice::create([
                'numero_apolice' => 'AP-VENC-' . rand(10000, 99999),
                'segurado_id' => $segurado->id,
                'user_id' => $corretor->id,
                'filial_id' => $filial->id,
                'cotacao_id' => $cotacao->id,
                'snapshot' => $snapshotExemplo,
                'dados_bem_assegurado' => $dadosBemExemplo,
                'status' => 'Vigente',
                'data_emissao' => Carbon::now()->subMonths(11),
                'data_inicio' => Carbon::now()->subMonths(11),
                'data_fim' => Carbon::now()->addDays($i * 5),
                'valor_total' => rand(1500, 5000),
                'forma_pagamento' => 'Cartão de Crédito',
                'quantidade_parcelas' => 10,
                'valor_parcela' => 300.00,
            ]);
        }

        $apoliceVigente = Apolice::where('status', 'Vigente')->first();

        if ($apoliceVigente) {
            Sinistro::create([
                'apolice_id' => $apoliceVigente->id,
                'data_hora_ocorrencia' => Carbon::now()->subDays(2),
                'status' => 'Em análise',
                'rua' => 'Av. das Américas',
                'numero' => '1000',
                'bairro' => 'Centro',
                'cidade' => 'Montes Claros',
                'uf' => 'MG',
                'cep' => '39400000',
                'descricao' => 'Colisão traseira leve em semáforo.',
                'coberturas_envolvidas' => ['Colisão e Danos Materiais'],
            ]);
        }
    }
}
