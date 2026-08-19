<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\Apolice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmissaoApoliceService
{
    public function emitir(Cotacao $cotacao, string $formaPagamento, int $quantidadeParcelas): Apolice
    {
        return DB::transaction(function () use ($cotacao, $formaPagamento, $quantidadeParcelas) {

            $snapshot = [
                'produto' => [
                    'id'   => $cotacao->produto->id ?? null,
                    'nome' => $cotacao->produto->nome ?? 'Produto Desconhecido',
                ],
                'coberturas' => $cotacao->cobertura_selecionada,
            ];

            $valorParcela = $quantidadeParcelas > 0 
                ? ($cotacao->valor_total / $quantidadeParcelas) 
                : $cotacao->valor_total;

            $apolice = Apolice::create([
                'segurado_id'          => $cotacao->segurado_id,
                'user_id'              => $cotacao->user_id,
                'filial_id'            => $cotacao->filial_id,
                'cotacao_id'           => $cotacao->id,
                'apolice_origem_id'    => null, 
                'numero_apolice'       => 'AP-' . str_pad(random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
                'data_emissao'         => Carbon::now(),
                'data_inicio'          => Carbon::now(),
                'data_fim'             => Carbon::now()->addYear(),
                'status'               => 'Vigente',
                'snapshot'             => $snapshot, 
                'dados_bem_assegurado' => $cotacao->dados_especificos, 
                'beneficiarios'        => [], 
                'forma_pagamento'      => $formaPagamento,
                'quantidade_parcelas'  => $quantidadeParcelas,
                'valor_parcela'        => $valorParcela,
                'valor_total'          => $cotacao->valor_total,
            ]);


            $beneficiariosJson = $cotacao->dados_especificos['beneficiarios_vida'] ?? [];

            foreach ($beneficiariosJson as $ben) {
                if (empty($ben['cpf']) || empty($ben['nome'])) {
                    continue;
                }
                
                $beneficiario = \App\Models\Beneficiario::firstOrCreate(
                    ['cpf' => $ben['cpf']],
                    [
                        'nome' => $ben['nome'],
                        'data_nascimento' => null
                    ]
                );

                $apolice->beneficiarios()->attach($beneficiario->id, [
                    'percentual_rateio' => $ben['percentual_rateio'],
                    'parentesco'        => $ben['parentesco'],
                ]);
            }

          
            for ($i = 1; $i <= $quantidadeParcelas; $i++) {
                $isPrimeiraParcela = ($i === 1);

                \App\Models\Pagamento::create([
                    'apolice_id'     => $apolice->id,
                    'num_parcela'    => $i,
                    'tipo_movimentacao' => 'Recebimento',
                    'valor'          => $valorParcela,
                    'data_vencimento'     => Carbon::now()->addMonths($i - 1), // Vencimentos mensais
                    // A primeira parcela já nasce paga devido ao aceite no checkout
                    'status'         => $isPrimeiraParcela ? 'Paga' : 'Aberta',
                    'data_pagamento' => $isPrimeiraParcela ? Carbon::now() : null,
                    'metodo_baixa'   => $isPrimeiraParcela ? 'Automática' : null,

                ]);
            }

            return $apolice;
        });
    }
}