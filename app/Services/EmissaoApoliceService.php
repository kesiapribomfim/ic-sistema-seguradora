<?php

namespace App\Services;

use App\Models\Apolice;
use App\Models\Beneficiario;
use App\Models\Cotacao;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmissaoApoliceService
{
    public function emitir(Cotacao $cotacao, string $formaPagamento, int $quantidadeParcelas): Apolice
    {
        $quantidadeParcelas = max(1, $quantidadeParcelas);

        return DB::transaction(function () use ($cotacao, $formaPagamento, $quantidadeParcelas) {

            $apolice = $this->gerarApolice($cotacao, $formaPagamento, $quantidadeParcelas);

            $this->vincularBeneficiarios($apolice, $cotacao);

            $this->gerarParcelas($apolice, $quantidadeParcelas);

            return $apolice;
        });
    }

    private function emitirSnapshot(Cotacao $cotacao): array
    {
        $snapshot = [
            'produto' => [
                'id'    => $cotacao->produto->id ?? null,
                'nome'  => $cotacao->produto->nome ?? 'Produto Desconhecido',
            ],
            'coberturas' => $cotacao->cobertura_selecionada,
        ];
        return $snapshot;
    }

    private function gerarApolice(Cotacao $cotacao, string $formaPagamento, int $quantidadeParcelas): Apolice
    {
        $valorParcela = $cotacao->valor_total / $quantidadeParcelas;
        $dadosEspecificos = $cotacao->dados_especificos ?? [];
        $apoliceOrigemId = $dadosEspecificos['apolice_origem_id_temporario'] ?? null;

        unset($dadosEspecificos['apolice_origem_id_temporario']);

        $apolice = Apolice::create([
            'segurado_id' => $cotacao->segurado_id,
            'user_id' => $cotacao->user_id,
            'filial_id' => $cotacao->filial_id,
            'cotacao_id' => $cotacao->id,
            'apolice_origem_id' => $apoliceOrigemId,
            'numero_apolice' => 'AP-' . str_pad(random_int(1, 99999999), 8, '0', STR_PAD_LEFT),
            'data_emissao' => Carbon::now(),
            'data_inicio' => Carbon::now(),
            'data_fim' => Carbon::now()->addYear(),
            'status' => 'Vigente',
            'snapshot' => $this->emitirSnapshot($cotacao),
            'dados_bem_assegurado' => $dadosEspecificos,
            'beneficiarios' => [],
            'forma_pagamento' => $formaPagamento,
            'quantidade_parcelas' => $quantidadeParcelas,
            'valor_parcela' => $valorParcela,
            'valor_total' => $cotacao->valor_total,
        ]);

        return $apolice;
    }

    private function vincularBeneficiarios(Apolice $apolice, Cotacao $cotacao): void
    {
        $beneficiariosJson = $cotacao->dados_especificos['beneficiarios_vida'] ?? [];

        foreach ($beneficiariosJson as $ben) {
            if (empty($ben['cpf']) || empty($ben['nome'])) {
                continue;
            }

            $beneficiario = Beneficiario::firstOrCreate(
                ['cpf' => $ben['cpf']],
                [
                    'nome' => $ben['nome'],
                    'data_nascimento' => null,
                ]
            );

            $apolice->beneficiarios()->attach($beneficiario->id, [
                'percentual_rateio' => $ben['percentual_rateio'],
                'parentesco' => $ben['parentesco'],
            ]);
        }
    }

    private function gerarParcelas(Apolice $apolice, int $quantidadeParcelas): void 
    {
        for ($i = 1; $i <= $quantidadeParcelas; $i++) {
                $isPrimeiraParcela = ($i === 1);

                Pagamento::create([
                    'apolice_id' => $apolice->id,
                    'num_parcela' => $i,
                    'tipo_movimentacao' => 'Recebimento',
                    'valor' => $apolice->valor_parcela,
                    'data_vencimento' => Carbon::now()->addMonths($i - 1),
                    'status' => $isPrimeiraParcela ? 'Paga' : 'Aberta',
                    'data_pagamento' => $isPrimeiraParcela ? Carbon::now() : null,
                    'metodo_baixa' => $isPrimeiraParcela ? 'Automática' : null,
                ]);
            }
    }
}
