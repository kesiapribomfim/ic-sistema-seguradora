<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sinistro;
use App\Models\Apolice;
use App\Models\User;

class SinistroSeeder extends Seeder
{
    public function run(): void
    {
        $apolicesVigentes = Apolice::where('status', 'Vigente')->get();

        if ($apolicesVigentes->isEmpty()) {
            $this->command->warn('Nenhuma Apólice vigente encontrada. Sinistros não foram gerados.');
            return;
        }

        $this->command->info('Criando histórico de sinistros com coberturas reais...');

        $apolicesSistradas = $apolicesVigentes->random(min(15, $apolicesVigentes->count()));

        foreach ($apolicesSistradas as $apolice) {

            $coberturasContratadas = $apolice->snapshot['coberturas'] ?? [];

            if (empty($coberturasContratadas)) continue;

            $coberturaAfetada = fake()->randomElement($coberturasContratadas);
            $nomeCobertura = $coberturaAfetada['nome_cobertura'] ?? 'o bem segurado';
            $limiteCobertura = (float) ($coberturaAfetada['limite_maximo'] ?? 10000);

            $status = fake()->randomElement([
                'Em análise',
                'Aguardando Gestor',
                'Aprovado',
                'Negado',
                'Pago',
                'Encerrado'
            ]);

            $valorIndenizacao = null;
            $valorPago = null;

            if (in_array($status, ['Aprovado', 'Pago', 'Encerrado', 'Aguardando Gestor'])) {
                $valorIndenizacao = fake()->randomFloat(2, 500, $limiteCobertura);
            }

            if (in_array($status, ['Pago', 'Encerrado'])) {
                $valorPago = $valorIndenizacao;
            }

            $sinistro = Sinistro::create([
                'apolice_id' => $apolice->id,

                'data_hora_ocorrencia' => fake()->dateTimeBetween($apolice->data_inicio, 'now'),

                'rua' => fake()->streetName(),
                'numero' => fake()->buildingNumber(),
                'bairro' => fake()->citySuffix(),
                'complemento' => fake()->optional(0.3)->secondaryAddress(),
                'cidade' => fake()->city(),
                'uf' => fake()->stateAbbr(),
                'cep' => fake()->numerify('##.###-###'),

                'descricao' => "Cliente acionou o seguro informando um incidente relacionado a {$nomeCobertura}. Documentação inicial anexada para análise técnica.",

                'coberturas_envolvidas' => [$coberturaAfetada],

                'status' => $status,
                'valor_indenizacao' => $valorIndenizacao,
                'valor_pago' => $valorPago,
            ]);

            if ($valorPago > 0 && in_array($status, ['Pago', 'Encerrado'])) {
                \App\Models\Pagamento::create([
                    'apolice_id'        => $apolice->id,
                    'sinistro_id'       => $sinistro->id,
                    'num_parcela'       => null,
                    'tipo_movimentacao' => 'Pagamento Indenização',
                    'valor'             => $valorPago,
                    'data_vencimento'   => now(),
                    'status'            => 'Paga',
                    'data_pagamento'    => now(),
                    'metodo_baixa'      => 'Automática',
                ]);
            }
        }

        $this->command->info('Sinistros gerados com sucesso!');
    }
}
