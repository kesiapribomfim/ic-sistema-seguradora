<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Apolice;
use App\Models\Cotacao;

class ApoliceSeeder extends Seeder
{
    public function run(): void
    {
        // Busca apenas as cotações que tiveram o status "Aceita"
        $cotacoesAceitas = Cotacao::with(['produto', 'segurado', 'user', 'filial'])
            ->where('status', 'Aceita')
            ->get();

        if ($cotacoesAceitas->isEmpty()) {
            $this->command->warn('Nenhuma Cotação com status "Aceita" encontrada. Apólices não foram geradas.');
            return;
        }

        $this->command->info('Emitindo Apólices com diferentes períodos de vigência...');

        $contador = 0;

        foreach ($cotacoesAceitas as $cotacao) {

            if ($contador === 0) {
                $dataEmissao = now()->subMonths(11)->subDays(25); // Quase vencendo
                $status = 'Vigente';
            } elseif ($contador === 1) {
                $dataEmissao = now()->subMonths(13); // Expirada
                $status = 'Expirada';
            } elseif ($contador === 2) {
                $dataEmissao = now()->subMonths(6); // Cancelada
                $status = 'Cancelada';
            } else {
                $dataEmissao = now()->subMonths(fake()->numberBetween(1, 3)); // Novas
                $status = 'Vigente';
            }

            $dataInicio = clone $dataEmissao;
            $dataFim = (clone $dataInicio)->addYear();

            $valorTotal = $cotacao->valor_total ?? fake()->randomFloat(2, 1000, 5000);
            $formaPagamento = fake()->randomElement(['Cartão de Crédito', 'Boleto Bancário', 'Pix']);
            $quantidadeParcelas = ($formaPagamento === 'Pix') ? 1 : fake()->numberBetween(1, 12);
            $valorParcela = $valorTotal / $quantidadeParcelas;

            $coberturasSnapshot = collect($cotacao->cobertura_selecionada ?? [])->values()->toArray();

            $snapshot = [
                'produto' => [
                    'id' => $cotacao->produto->id,
                    'nome' => $cotacao->produto->nome,
                    'ramo' => $cotacao->produto->ramo,
                ],
                'coberturas' => $coberturasSnapshot, // O SINISTRO PRECISA DISSO AQUI!
            ];

            $apolice = Apolice::create([
                'segurado_id' => $cotacao->segurado_id,
                'user_id' => $cotacao->user_id,
                'filial_id' => $cotacao->filial_id,
                'cotacao_id' => $cotacao->id,
                'apolice_origem_id' => null,
                'numero_apolice' => 'AP-' . $dataEmissao->format('Ymd') . '-' . fake()->unique()->numerify('####'),
                'data_emissao' => $dataEmissao,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => $status,
                'snapshot' => $snapshot,
                'dados_bem_assegurado' => $cotacao->dados_especificos ?? [],
                'beneficiarios' => $cotacao->dados_especificos['beneficiarios_vida'] ?? null,
                'forma_pagamento' => $formaPagamento,
                'quantidade_parcelas' => $quantidadeParcelas,
                'valor_parcela' => $valorParcela,
                'valor_total' => $valorTotal,
            ]);

            for ($i = 1; $i <= $quantidadeParcelas; $i++) {
                $isPrimeiraParcela = ($i === 1);
                
                $dataVencimento = (clone $dataEmissao)->addMonths($i - 1);

                \App\Models\Pagamento::create([
                    'apolice_id'        => $apolice->id,
                    'num_parcela'       => $i,
                    'tipo_movimentacao' => 'Recebimento',
                    'valor'             => $valorParcela,
                    'data_vencimento'   => $dataVencimento,
                    
                    'status'            => ($isPrimeiraParcela || $status === 'Renovada') ? 'Paga' : 'Aberta',
                    'data_pagamento'    => ($isPrimeiraParcela || $status === 'Renovada') ? clone $dataVencimento : null,
                    'metodo_baixa'      => ($isPrimeiraParcela || $status === 'Renovada') ? 'Automática' : null,
                ]);
            }

            $contador++;
        }

        $this->command->info('Apólices emitidas com sucesso!');
    }
}
