<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

use App\Models\Produto;

class CalculadoraPremioService
{
    // Função auxiliar para evitar o erro da vírgula do PHP
    private function formatarNumero($valor): float
    {
        if (empty($valor)) return 0.0;
        // Troca vírgula por ponto e converte para float
        return (float) str_replace(',', '.', (string) $valor);
    }

    public function calcular(Produto $produto, array $dados): float
    {
        $parametros = $produto->parametros_calculo ?? [];

        $dadosEspecificos = $dados['dados_especificos'] ?? [];
        $dados = array_merge($dados, $dadosEspecificos);
        
        // Agora usamos a função auxiliar para ler a taxa
        $taxaBasePercentual = $this->formatarNumero($parametros['taxa_base'] ?? 0);

        // Lendo o campo do frontend (certifique-se de que ele enviou com ponto ou sem separador)
        $valorBaseRisco = $this->formatarNumero($dados['valor_base_risco'] ?? 0);

        // A Base em Reais
        $premioBase = $valorBaseRisco * ($taxaBasePercentual / 100);

        $totalAgravantes = 0;
        $totalDescontos = 0;
        $adicionaisCoberturas = 0;

        // =========================================================================
        // RAMO: AUTO
        // =========================================================================
        if ($produto->ramo === 'Auto') {
            
            $anoVeiculo = (int) ($dados['ano'] ?? date('Y'));
            if ((date('Y') - $anoVeiculo) > 10) {
                $totalAgravantes += (float) ($parametros['fator_veiculo_antigo'] ?? 0);
            }

            $tipoVeiculo = $dados['tipo_veiculo'] ?? '';
            if ($tipoVeiculo === 'moto') {
                $totalAgravantes += (float) ($parametros['fator_tipo_moto'] ?? 0);
            } elseif ($tipoVeiculo === 'caminhao') {
                $totalAgravantes += (float) ($parametros['fator_tipo_caminhao'] ?? 0);
            }

            if (!empty($dados['kit_gas'])) $totalAgravantes += (float) ($parametros['fator_kit_gas'] ?? 0);
            if (!empty($dados['blindado'])) $totalAgravantes += (float) ($parametros['fator_blindado'] ?? 0);
            
            if (!empty($dados['zero'])) $totalDescontos += (float) ($parametros['desconto_zero_km'] ?? 0);

            $uso = $dados['uso'] ?? [];
            if (is_array($uso) && in_array('comercial', $uso)) {
                $totalAgravantes += (float) ($parametros['fator_uso_comercial'] ?? 0);
            }

            $estacionamento = $dados['estacionamento'] ?? '';
            if ($estacionamento === 'rua') {
                $totalAgravantes += (float) ($parametros['fator_estacionamento_rua'] ?? 0);
            } elseif ($estacionamento === 'garagem') {
                $totalDescontos += (float) ($parametros['desconto_garagem'] ?? 0);
            }

            $usoAnterior = $dados['uso_anterior'] ?? 'nao';
            if ($usoAnterior !== 'nao' && !empty($dados['seguro_antigo'])) {
                $totalAgravantes += (float) ($parametros['fator_sinistro_anterior'] ?? 0);
            }

            $classeBonus = (int) ($dados['classe_bonus'] ?? 0);
            if ($classeBonus > 0) {
                $fatorBonus = (float) ($parametros['desconto_por_classe_bonus'] ?? 0);
                $totalDescontos += ($classeBonus * $fatorBonus);
            }
        }

        // ... (Deixe os ifs do Residencial e Vida intactos aqui no meio) ...

        // =========================================================================
        // PROCESSAMENTO DE COBERTURAS
        // =========================================================================
        // ATENÇÃO AQUI: Corrigimos o nome para o singular que está no seu form
        $coberturasSelecionadas = $dados['cobertura_selecionada'] ?? []; 
        
        if (is_array($coberturasSelecionadas)) {
            foreach ($coberturasSelecionadas as $cob) {
                if (!empty($cob['contratada']) && empty($cob['obrigatoria'])) {
                    $limite = (float) ($cob['limite_maximo'] ?? 0);
                    $adicionaisCoberturas += ($limite * 0.01); 
                }
            }
        }

        // =========================================================================
        // A MATEMÁTICA FINAL
        // =========================================================================
        $fatorMultiplicador = 1 + ($totalAgravantes / 100) - ($totalDescontos / 100);
        $fatorMultiplicador = max($fatorMultiplicador, 0.1); 

        Log::info('CalculadoraPremioService::calcular', [
            'dados_brutos' => $dados,
            'dados_especificos' => $dadosEspecificos,
            'taxaBasePercentual' => $taxaBasePercentual,
            'valorBaseRisco' => $valorBaseRisco,
            'premioBase' => $premioBase,
            'totalAgravantes' => $totalAgravantes,
            'totalDescontos' => $totalDescontos,
            'fatorMultiplicador' => $fatorMultiplicador,
            'adicionaisCoberturas' => $adicionaisCoberturas,
        ]);

        return ($premioBase * $fatorMultiplicador) + $adicionaisCoberturas;
    }
}