<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;

use App\Models\Produto;
use App\Models\Segurado;
use App\Enums\RamoEnum;

class CalculadoraPremioService
{

    public function calcular(Produto $produto, array $dados, Segurado $segurado): float
    {
        $score = $segurado->score ?? 0;
        $fatorScore = $this->calcularImpactoScore($score);

        $parametros = $produto->parametros_calculo ?? [];

        $dadosEspecificos = $dados['dados_especificos'] ?? [];
        $dados = array_merge($dados, $dadosEspecificos);
        
        $taxaBasePercentual = $this->formatarNumero($parametros['taxa_base'] ?? 0);
        $valorBaseRisco = $this->formatarNumero($dados['valor_base_risco'] ?? 0);


        $premioBase = $valorBaseRisco * ($taxaBasePercentual / 100);

        $totaisRamo = match ($produto->ramo) {
            RamoEnum::AUTO->value        => $this->calcularRamoAuto($dados, $parametros),
            RamoEnum::RESIDENCIAL->value => $this->calcularRamoResidencial($dados, $parametros),
            RamoEnum::VIDA->value        => $this->calcularRamoVida($dados, $parametros),
        };

        $totalAgravantes = $totaisRamo['agravante'];
        $totalDescontos = $totaisRamo['desconto'];
        

        $adicionaisCoberturas = $this->calcularCoberturasAdicionais($dados['cobertura_selecionada'] ?? []);

        $fatorMultiplicador = 1 + $fatorScore + ($totalAgravantes / 100) - ($totalDescontos / 100);
        $fatorMultiplicador = $this->limitarDesconto($fatorMultiplicador);

        $dadosLog = [
            'ramo_processado' => $produto->ramo,
            'taxaBasePercentual' => $taxaBasePercentual,
            'valorBaseRisco' => $valorBaseRisco,
            'premioBase' => $premioBase,
            'fatorScore' => $fatorScore,
            'totalAgravantes' => $totalAgravantes,
            'totalDescontos' => $totalDescontos,
            'fatorMultiplicador' => $fatorMultiplicador,
            'adicionaisCoberturas' => $adicionaisCoberturas,
        ];

        $this->informarCalculo($dadosLog);

        return ($premioBase * $fatorMultiplicador) + $adicionaisCoberturas;
    }

    //function para formatar numeros em caso de digitação com vírgula
    private function formatarNumero(float $valor): float
    {
        if (empty($valor)) return 0.0;
        return (float) str_replace(',', '.', (string) $valor);
    }

    //function para calcular score do segurado
    private function calcularImpactoScore (int $score): float
    {
        $fatorScore = 0.0;

        if ($score >= 80) {
            $fatorScore = -0.075; // 7.5% de desconto para score >= 80
        } elseif ($score < 50) {
             $fatorScore = 0.10; // 10% de acréscimo para score menor que 50
        }
        return $fatorScore;

    }

    //function para calcular agravantes e descontos do ramo Auto
    private function calcularRamoAuto (array $dados, array $parametros): array
    {
        $totalAgravantes = 0.0;
        $totalDescontos = 0.0;


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

        return ['agravante' => $totalAgravantes, 'desconto' => $totalDescontos];
        
    }

    //function para calcular agravantes e descontos do ramo Residencial
    private function calcularRamoResidencial (array $dados, array $parametros): array
    {
        $totalAgravantes = 0.0;
        $totalDescontos = 0.0;

        if (($dados['tipo_construcao'] ?? '') === 'madeira') {
            $totalAgravantes += (float) ($parametros['fator_construcao_madeira'] ?? 0);
        }

        if (($dados['uso_residencia'] ?? '') === 'veraneio') {
            $totalAgravantes += (float) ($parametros['fator_uso_veraneio'] ?? 0);
        }

        $sobreImovel = $dados['sobre_imovel'] ?? [];
        if (is_array($sobreImovel) && in_array('desocupado', $sobreImovel)) {
            $totalAgravantes += (float) ($parametros['fator_imovel_desocupado'] ?? 0);
        }

        if (($dados['regiao'] ?? '') === 'rural') {
            $totalAgravantes += (float) ($parametros['fator_regiao_rural'] ?? 0);
        }

        if (($dados['agro_comercial'] ?? '') === 'com_agro_comercial') {
            $totalAgravantes += (float) ($parametros['fator_agro_comercial'] ?? 0);
        }

        if (($dados['terreno_baldio'] ?? '') === 'sim') {
            $totalAgravantes += (float) ($parametros['fator_terreno_baldio'] ?? 0);
        }

        $sinistros = $dados['sinistros'] ?? 'nao';
        if (in_array($sinistros, ['uma_vez', 'duas_vezes', 'tres_mais'])) {
            $totalAgravantes += (float) ($parametros['fator_sinistro_anterior'] ?? 0);
        }

        $tipoMoradia = $dados['tipo_moradia'] ?? '';
        if ($tipoMoradia === 'apartamento') {
            $totalDescontos += (float) ($parametros['desconto_apartamento'] ?? 0);
        } elseif ($tipoMoradia === 'condominio_horizontal') {
            $totalDescontos += (float) ($parametros['desconto_condominio_horizontal'] ?? 0);
        }

        return ['agravante' => $totalAgravantes, 'desconto' => $totalDescontos];
    }

    //function para calcular agravantes e descontos do ramo Vida
    private function calcularRamoVida (array $dados, array $parametros): array
    {
        $totalAgravantes = 0.0;
        $totalDescontos = 0.0;


        // Lógica isolada para processar a saúde de qualquer pessoa (Titular ou Dependente)
        $processarRiscoSaude = function (array $pessoa) use ($parametros, &$totalAgravantes, &$totalDescontos) {

            // Profissão de Risco (Geralmente só aplicável ao titular, mas verificamos de forma segura)
            if (($pessoa['profissao_risco'] ?? 'nao') === 'sim') {
                $totalAgravantes += (float) ($parametros['fator_profissao_risco'] ?? 0);
            }

            // Cálculo de IMC
            $peso = $this->formatarNumero($pessoa['peso'] ?? 0);
            $altura = $this->formatarNumero($pessoa['altura'] ?? 0);

            if ($peso > 0 && $altura > 0) {
                $alturaMetros = $altura / 100;
                $imc = $peso / ($alturaMetros ** 2);

                // IMC fora do padrão (abaixo de 18.5 ou acima de 30)
                if ($imc < 18.5 || $imc >= 30) {
                    $totalAgravantes += (float) ($parametros['fator_imc_fora_padrao'] ?? 0);
                }
            }

            // Doenças Preexistentes
            $possuiDoenca = !empty($pessoa['possui_doenca_preexistente']);
            if ($possuiDoenca) {
                $doencasDiagnosticadas = $pessoa['doencas_diagnosticadas'] ?? [];
                $doencasGraves = ['cancer', 'avc', 'infarto', 'alzheimer', 'parkinson', 'esclerose_multipla'];

                // Verifica se há intersecção entre o array de doenças do paciente e a lista de doenças graves
                $temDoencaGrave = count(array_intersect($doencasDiagnosticadas, $doencasGraves)) > 0;

                if ($temDoencaGrave) {
                    $totalAgravantes += (float) ($parametros['fator_doenca_grave'] ?? 0);
                } else {
                    $totalAgravantes += (float) ($parametros['fator_doenca_preexistente'] ?? 0);
                }
            }

            $fumante = !empty($pessoa['fumante']);
            $alcool = !empty($pessoa['consome_alcool']);
            $esportesRadicais = !empty($pessoa['pratica_esportes_radicais']);

            if ($fumante) $totalAgravantes += (float) ($parametros['fator_fumante'] ?? 0);
            if ($alcool) $totalAgravantes += (float) ($parametros['fator_alcool'] ?? 0);
            if ($esportesRadicais) $totalAgravantes += (float) ($parametros['fator_esportes_radicais'] ?? 0);

            if (!$fumante && !$alcool && !$possuiDoenca) {
                $totalDescontos += (float) ($parametros['desconto_perfil_saudavel'] ?? 0);
            }
        };

        // Avalia o risco do Titular
        $processarRiscoSaude($dados);

        // Avalia o risco da família (Dependentes)
        $dependentes = $dados['dependentes_vida'] ?? [];
        if (is_array($dependentes) && !empty($dependentes)) {
            foreach ($dependentes as $dependente) {
                // Adiciona o agravante percentual base por familiar estar no plano
                $parentesco = $dependente['parentesco'] ?? '';
                $totalAgravantes += ($parentesco === 'conjuge') ? 10.0 : 5.0;

                // Avalia a saúde individual deste dependente
                $processarRiscoSaude($dependente);
            }
        }

        return ['agravante' => $totalAgravantes, 'desconto' => $totalDescontos];
    }

    //function para calcular coberturas adicionais
    private function calcularCoberturasAdicionais(array $coberturas): float
    {
        $adicionais = 0.0;

        if (is_array($coberturas)) {
            foreach ($coberturas as $cob) {
                if (!empty($cob['contratada']) && empty($cob['obrigatoria'])) {
                    $limite = $this->formatarNumero($cob['limite_maximo'] ?? 0);
                    $adicionais += ($limite * 0.01);
                }
            }
        }
        return $adicionais;
    }

    //function para limitar desconto para no máximo 90% do prêmio base
    private function limitarDesconto(float $valor): float
    {
        return max($valor, 0.1);
    }

    //function para log
    private function informarCalculo (array $dados): void
    {
        Log::info('CalculadoraPremioService::calcular', $dados);
    }
}