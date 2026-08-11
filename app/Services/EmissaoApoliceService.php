<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\Apolice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmissaoApoliceService
{
    public function emitir(Cotacao $cotacao, string $formaPagamento = 'Cartão de Crédito', int $quantidadeParcelas = 1): Apolice
    {
        return DB::transaction(function () use ($cotacao, $formaPagamento, $quantidadeParcelas) {
            
            $cotacao->update(['status' => 'Aceita']);

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
                'numero_apolice'       => 'AP-' . strtoupper(Str::random(8)), //numeros
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

            $primeiraParcela = \App\Models\Pagamento::where('apolice_id', $apolice->id)
                                ->where('num_parcela', 1)
                                ->first();

            if ($primeiraParcela) {
                $primeiraParcela->update([
                    'status'         => 'Paga',
                    'data_pagamento' => Carbon::now(),
                    'metodo_baixa'   => 'Automática'
                ]);
            }

            return $apolice;
        });
    }
}