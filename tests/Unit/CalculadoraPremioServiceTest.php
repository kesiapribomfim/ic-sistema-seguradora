<?php

use App\Services\CalculadoraPremioService;
use App\Models\Produto;
use App\Models\Segurado;
use Illuminate\Support\Facades\Log;

/**
 * Classe de teste destinada a Service CalculadoraPremioService
 */

uses(Tests\TestCase::class);


// =========================================================================
// TESTES SCORE
// =========================================================================
describe(
    'SCORE',
        function () {
            test ('deve calcular premio base corretamente aplicando o score', function ($score, $resultadoEsperado) {
                //ARRANGE
                $service = new CalculadoraPremioService();

                $produto = new Produto();
                $produto->ramo = 'Outro';
                $produto->parametros_calculo = ['taxa_base' => 5.0];
                
                $segurado = new Segurado();
                $segurado->score = $score;
                
                $dados = [
                    'valor_base_risco' => 100000
                ];

                //ACT
                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe($resultadoEsperado);
            })->with([
                'score alto' => [85, 4625.0],
                'score baixo'=> [15, 5500.0]
            ]);
        }
);


// =========================================================================
// RAMO: AUTO
// =========================================================================
describe(
    'RAMO: AUTO',
        function () {
            test('deve calcular agravamento do ramo auto', function($campoAgravante, $valorAgravante, $dadosChave, $dadoValor, $resultadoEsperado) {
                $service = new CalculadoraPremioService();
                $segurado = new Segurado(['score' => 50]);
                
                $produto = new Produto();
                $produto->ramo = 'Auto';
                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    $campoAgravante => $valorAgravante
                ];

                $dados = [
                    'ano' => (int) date('Y'),
                    'valor_base_risco' => 100000,
                    $dadosChave => $dadoValor
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe($resultadoEsperado);

            })->with([
                'veiculo antigo'    => ['fator_veiculo_antigo', 3.0, 'ano', (int) date('Y')-11, 5150.0],
                'tipo moto'           => ['fator_tipo_moto', 5.0, 'tipo_veiculo', 'moto', 5250.0],
                'tipo caminhao'       => ['fator_tipo_caminhao', 10.0, 'tipo_veiculo', 'caminhao', 5500.0],
                'tem kit gas'         => ['fator_kit_gas', 2.0, 'kit_gas', true, 5100.0],
                'eh blindado'         => ['fator_blindado', 4.0, 'blindado', true, 5200.0],
                'uso comercial'       => ['fator_uso_comercial', 6.0, 'uso', ['comercial'], 5300.0],
                'estaciona na rua'    => ['fator_estacionamento_rua', 3.0, 'estacionamento', 'rua', 5150.0],
            ]);

            test('deve calcular os descontos simples do ramo auto', function ($campoDesconto, $valorDesconto, $dadoChave, $dadoValor, $resultadoEsperado) {
                $service = new CalculadoraPremioService();
                $segurado = new Segurado(['score' => 50]); 
                
                $produto = new Produto();
                $produto->ramo = 'Auto';
                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    $campoDesconto => $valorDesconto 
                ];

                $dados = [
                    'valor_base_risco' => 100000,
                    'ano' => (int) date('Y'),
                    $dadoChave => $dadoValor
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);
                expect($resultado)->toBe($resultadoEsperado);

            })->with([
                'carro zero km'       => ['desconto_zero_km', 3.0, 'zero', true, 4850.0],
                'garagem fechada'     => ['desconto_garagem', 2.0, 'estacionamento', 'garagem', 4900.0],
            ]);
            test('deve aplicar agravante se tiver seguro antigo e uso anterior', function () {
                $service = new CalculadoraPremioService();
                $produto = new Produto(['ramo' => 'Auto', 'parametros_calculo' => [
                    'taxa_base' => 5.0,
                    'fator_sinistro_anterior' => 3.0 // Agravante de 3%
                ]]);
                $segurado = new Segurado(['score' => 50]); 
                
                $dados = [
                    'valor_base_risco' => 100000,
                    'uso_anterior' => 'sim', // Chave 1
                    'seguro_antigo' => true  // Chave 2 (ambas são necessárias)
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);
                expect($resultado)->toBe(5150.0);
            });

            test('deve calcular o desconto multiplicando a classe de bonus', function () {
                $service = new CalculadoraPremioService();
                $produto = new Produto(['ramo' => 'Auto', 'parametros_calculo' => [
                    'taxa_base' => 5.0,
                    'desconto_por_classe_bonus' => 2.0 // 2% de desconto POR CLASSE
                ]]);
                $segurado = new Segurado(['score' => 50]); 
                
                $dados = [
                    'valor_base_risco' => 100000,
                    'classe_bonus' => 3 // Matemática: 3 classes * 2% = 6% de desconto total
                ];

                    // Prêmio base (5.000) - 6% (300) = 4.700
                    $resultado = $service->calcular($produto, $dados, $segurado);
                    expect($resultado)->toBe(4700.0);
                });
        }
);
// =========================================================================
// RAMO: RESIDENCIAL
// =========================================================================
describe(
    'RAMO: RESIDENCIAL',
        function () {
            test('deve calcular agravantes do ramo residencial', function ($campoAgravante, $valorAgravante, $dadoChave, $dadoValor, $resultadoEsperado) {
                
                $service = new CalculadoraPremioService();

                $segurado = new Segurado(['score' => 50]); 
                
                $produto = new Produto();
                $produto->ramo = 'Residencial';

                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    $campoAgravante => $valorAgravante 
                ];

                $dados = [
                    'valor_base_risco' => 100000,
                    $dadoChave => $dadoValor
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe($resultadoEsperado);

            })->with([
                'construcao em madeira'     => ['fator_construcao_madeira', 3.0, 'tipo_construcao', 'madeira', 5150.0],
                'casa de veraneio'          => ['fator_uso_veraneio', 5.0, 'uso_residencia', 'veraneio', 5250.0],
                'regiao rural'              => ['fator_regiao_rural', 10.0, 'regiao', 'rural', 5500.0],
                'terreno baldio'            => ['fator_terreno_baldio', 3.0, 'terreno_baldio', 'sim', 5150.0],
                'sinistro vez'              => ['fator_sinistro_anterior', 3.0, 'sinistros', 'uma_vez', 5150.0],
                'sinistros duas vezes'      => ['fator_sinistro_anterior', 5.0, 'sinistros', 'duas_vezes', 5250.0],
                'sinistros tres ou mais'    => ['fator_sinistro_anterior', 10.0, 'sinistros', 'tres_mais', 5500.0],
                'imovel desocupado'         => ['fator_imovel_desocupado', 4.0, 'sobre_imovel', ['desocupado'], 5200.0],
                'com agro comercial'        => ['fator_agro_comercial', 4.0, 'agro_comercial', 'com_agro_comercial', 5200.0]
            ]);

            test('deve calcular os descontos do ramo residencial', function ($campoDesconto, $valorDesconto, $dadosChave, $dadoValor, $resultadoEsperado) {
                $service = new CalculadoraPremioService;

                $segurado = new Segurado(['score' => 50]); 
                
                $produto = new Produto();
                $produto->ramo = 'Residencial';

                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    $campoDesconto => $valorDesconto 
                ];

                $dados = [
                    'valor_base_risco' => 100000,
                    $dadosChave => $dadoValor
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe($resultadoEsperado);
            })->with([
                'apartamento'  => ['desconto_apartamento', 3.0, 'tipo_moradia', 'apartamento', 4850.0],
                'condominio'   => ['desconto_condominio_horizontal', 3.0, 'tipo_moradia', 'condominio_horizontal', 4850.0],
            ]);
        }
);
// =========================================================================
// RAMO: VIDA
// =========================================================================
describe(
    'RAMO: VIDA',
        function () {
            test('deve calcular agravante do ramo vida', function ($campoAgravante, $valorAgravante, $dadosChave, $dadoValor, $resultadoEsperado){
                $service = new CalculadoraPremioService;
                $segurado = new Segurado([
                    'score' => 50,
                ]);
                $produto = new Produto();
                $produto->ramo = 'Vida';
                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    $campoAgravante => $valorAgravante,

                ];
                $dados = [
                    'valor_base_risco' => 100000,
                    $dadosChave => $dadoValor,
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe($resultadoEsperado);
            })->with([
                'profissional de risco' => ['fator_profissao_risco', 3.0, 'profissao_risco', 'sim', 5150.0],
                'fumante' => ['fator_fumante', 5.0 , 'fumante', true, 5250.00],
                'consome bebida alcoolica' => ['fator_alcool', 5.0, 'consome_alcool', true, 5250.00],
                'esportes radicais' => ['fator_esportes_radicais', 5.0, 'pratica_esportes_radicais', true, 5250.00],
            ]);


            test('deve calcular desconto de perfil saudavel', function (){
                $service = new CalculadoraPremioService;
                $segurado = new Segurado (['score' => 50]);
                $produto = new Produto();
                $produto->ramo = 'Vida';
                $produto->parametros_calculo = [
                    'taxa_base' => 5.0,
                    'desconto_perfil_saudavel' => 10.0
                ];

                $dados = [
                    'valor_base_risco' => 100000,
                    'fumante' => false,
                    'consome_alcool' => false,
                    'possui_doenca_preexistente' => false,
                ];

                $resultado = $service->calcular($produto, $dados, $segurado);

                expect($resultado)->toBe(4500.0);

            });
        }
);

describe(
    'LOG', function() {
        test('deve registrar os dados matematicos no log de auditoria', function () {
            Log::shouldReceive('info')
                ->once() 
                ->withArgs(function ($mensagem, $contexto) {
                    return $mensagem === 'CalculadoraPremioService::calcular'
                        && $contexto['ramo_processado'] === 'Outro'
                        && array_key_exists('fatorMultiplicador', $contexto);
                });

            $service = new CalculadoraPremioService();
            $segurado = new Segurado(['score' => 50]);
            $produto = new Produto(['ramo' => 'Outro', 'parametros_calculo' => ['taxa_base' => 5.0]]);
            $dados = ['valor_base_risco' => 100000];

            $service->calcular($produto, $dados, $segurado);

        });
    }
);