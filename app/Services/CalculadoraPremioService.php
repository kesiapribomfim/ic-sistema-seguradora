<?php

namespace App\Services;

use App\Models\Produto;

class CalculadoraPremioService
{
    /**
     * Calcula o prêmio final da apólice.
     * 
     * @param Produto $produto O modelo do produto com os parametros_calculo
     * @param array $dados As respostas do cliente (placa, idade, cep, coberturas, etc)
     * @return float O valor calculado
     */
    public function calcular(Produto $produto, array $dados): float
    {
        $parametros = $produto->parametros_calculo ?? [];
        $taxaBase = (float) ($parametros['taxa_base'] ?? 0);

        $totalAgravantes = 0;
        $totalDescontos = 0;
        $adicionaisCoberturas = 0;

        // =========================================================================
        // RAMO: AUTO
        // =========================================================================
        if ($produto->ramo === 'Auto') {
            
            // 1. Veículo Antigo (Calculado dinamicamente pelo Ano)
            $anoVeiculo = (int) ($dados['ano'] ?? date('Y'));
            if ((date('Y') - $anoVeiculo) > 10) {
                $totalAgravantes += (float) ($parametros['fator_veiculo_antigo'] ?? 0);
            }

            // 2. Tipo de Veículo
            $tipoVeiculo = $dados['tipo_veiculo'] ?? '';
            if ($tipoVeiculo === 'moto') {
                $totalAgravantes += (float) ($parametros['fator_tipo_moto'] ?? 0);
            } elseif ($tipoVeiculo === 'caminhao') {
                $totalAgravantes += (float) ($parametros['fator_tipo_caminhao'] ?? 0);
            }

            // 3. Características do Veículo
            if (!empty($dados['kit_gas'])) $totalAgravantes += (float) ($parametros['fator_kit_gas'] ?? 0);
            if (!empty($dados['blindado'])) $totalAgravantes += (float) ($parametros['fator_blindado'] ?? 0);
            
            // 4. Desconto Zero KM
            if (!empty($dados['zero'])) $totalDescontos += (float) ($parametros['desconto_zero_km'] ?? 0);

            // 5. Uso Comercial
            $uso = $dados['uso'] ?? [];
            if (is_array($uso) && in_array('comercial', $uso)) {
                $totalAgravantes += (float) ($parametros['fator_uso_comercial'] ?? 0);
            }

            // 6. Estacionamento / Pernoite
            $estacionamento = $dados['estacionamento'] ?? '';
            if ($estacionamento === 'rua') {
                $totalAgravantes += (float) ($parametros['fator_estacionamento_rua'] ?? 0);
            } elseif ($estacionamento === 'garagem') {
                $totalDescontos += (float) ($parametros['desconto_garagem'] ?? 0);
            }

            // 7. Histórico e Sinistros Anteriores
            $usoAnterior = $dados['uso_anterior'] ?? 'nao';
            if ($usoAnterior !== 'nao' && !empty($dados['seguro_antigo'])) {
                $totalAgravantes += (float) ($parametros['fator_sinistro_anterior'] ?? 0);
            }

            // 8. Multiplicador de Classe de Bônus
            $classeBonus = (int) ($dados['classe_bonus'] ?? 0);
            if ($classeBonus > 0) {
                $fatorBonus = (float) ($parametros['desconto_por_classe_bonus'] ?? 0);
                $totalDescontos += ($classeBonus * $fatorBonus);
            }
        }

        // =========================================================================
        // RAMO: RESIDENCIAL
        // =========================================================================
        if ($produto->ramo === 'Residencial') {
            
            // Fatores de Construção e Uso
            if (($dados['tipo_construcao'] ?? '') === 'madeira') $totalAgravantes += (float) ($parametros['fator_construcao_madeira'] ?? 0);
            if (($dados['uso_residencia'] ?? '') === 'veraneio') $totalAgravantes += (float) ($parametros['fator_uso_veraneio'] ?? 0);
            
            // Vacância (Imóvel Desocupado)
            $sobreImovel = $dados['sobre_imovel'] ?? [];
            if (is_array($sobreImovel) && in_array('desocupado', $sobreImovel)) {
                $totalAgravantes += (float) ($parametros['fator_imovel_desocupado'] ?? 0);
            }
            
            // Localização e Divisas
            if (($dados['regiao'] ?? '') === 'rural') $totalAgravantes += (float) ($parametros['fator_regiao_rural'] ?? 0);
            if (($dados['agro_comercial'] ?? '') === 'com_agro_comercial') $totalAgravantes += (float) ($parametros['fator_agro_comercial'] ?? 0);
            if (($dados['terreno_baldio'] ?? '') === 'sim') $totalAgravantes += (float) ($parametros['fator_terreno_baldio'] ?? 0);
            
            // Sinistralidade
            $sinistros = $dados['sinistros'] ?? 'nao';
            if ($sinistros !== 'nao') $totalAgravantes += (float) ($parametros['fator_sinistro_anterior'] ?? 0);

            // Descontos por Tipo de Moradia (Segurança)
            $tipoMoradia = $dados['tipo_moradia'] ?? '';
            if ($tipoMoradia === 'apartamento') $totalDescontos += (float) ($parametros['desconto_apartamento'] ?? 0);
            if ($tipoMoradia === 'condominio_horizontal') $totalDescontos += (float) ($parametros['desconto_condominio_horizontal'] ?? 0);
        }

        // =========================================================================
        // RAMO: VIDA
        // =========================================================================
        if ($produto->ramo === 'Vida') {
            
            // Agravantes Diretos
            if (($dados['profissao_risco'] ?? 'nao') === 'sim') $totalAgravantes += (float) ($parametros['fator_profissao_risco'] ?? 0);
            if (!empty($dados['fumante'])) $totalAgravantes += (float) ($parametros['fator_fumante'] ?? 0);
            if (!empty($dados['consome_alcool'])) $totalAgravantes += (float) ($parametros['fator_alcool'] ?? 0);
            if (!empty($dados['pratica_esportes_radicais'])) $totalAgravantes += (float) ($parametros['fator_esportes_radicais'] ?? 0);

            // Verificação Dinâmica de IMC (Peso e Altura)
            $peso = (float) ($dados['peso'] ?? 0);
            $alturaCm = (float) ($dados['altura'] ?? 0);
            
            if ($peso > 0 && $alturaCm > 0) {
                $alturaM = $alturaCm / 100;
                $imc = $peso / ($alturaM * $alturaM);
                
                // Penaliza desnutrição severa ou obesidade (IMC fora de 18.5 a 30)
                if ($imc < 18.5 || $imc > 30) {
                    $totalAgravantes += (float) ($parametros['fator_imc_fora_padrao'] ?? 0);
                }
            }

            // Histórico de Doenças
            if (!empty($dados['possui_doenca_preexistente'])) {
                $doencas = $dados['doencas_diagnosticadas'] ?? [];
                $doencasGraves = ['cancer', 'avc', 'infarto', 'alzheimer', 'parkinson', 'esclerose_multipla'];
                
                // Interseção para descobrir se há alguma doença grave na lista marcada
                $temGrave = is_array($doencas) && count(array_intersect($doencas, $doencasGraves)) > 0;

                if ($temGrave) {
                    $totalAgravantes += (float) ($parametros['fator_doenca_grave'] ?? 0);
                } else {
                    $totalAgravantes += (float) ($parametros['fator_doenca_preexistente'] ?? 0);
                }
            }

            // Desconto para Perfil Totalmente Saudável
            if (empty($dados['fumante']) && empty($dados['consome_alcool']) && empty($dados['possui_doenca_preexistente'])) {
                $totalDescontos += (float) ($parametros['desconto_perfil_saudavel'] ?? 0);
            }
        }

        // =========================================================================
        // PROCESSAMENTO DE COBERTURAS
        // =========================================================================
        // O formulário de cotação envia um array 'coberturas' com as marcações do corretor.
        $coberturasSelecionadas = $dados['coberturas'] ?? []; 
        
        if (is_array($coberturasSelecionadas)) {
            foreach ($coberturasSelecionadas as $cob) {
                // Se a cobertura opcional estiver marcada como "contratada" (true)
                if (!empty($cob['contratada']) && empty($cob['obrigatoria'])) {
                    
                    $limite = (float) ($cob['limite_maximo'] ?? 0);
                    
                    // Simulação: A seguradora cobra 1% do limite máximo da cobertura opcional
                    // Exemplo: Limite de Danos a Terceiros de R$ 50.000 -> Adiciona R$ 500 ao prêmio
                    $adicionaisCoberturas += ($limite * 0.01); 
                }
            }
        }

        // =========================================================================
        // A MATEMÁTICA FINAL
        // =========================================================================
        // Transforma percentuais em multiplicador (ex: +20% e -5% = 1.15)
        $fatorMultiplicador = 1 + ($totalAgravantes / 100) - ($totalDescontos / 100);
        
        // Trava de Segurança: impede que descontos exagerados zerem ou negativem o prêmio
        $fatorMultiplicador = max($fatorMultiplicador, 0.1); 

        // Aplica o multiplicador sobre a Taxa Base e soma o custo extra das coberturas
        return ($taxaBase * $fatorMultiplicador) + $adicionaisCoberturas;
    }
}