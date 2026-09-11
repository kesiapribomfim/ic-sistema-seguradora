<?php

use App\Services\CalculadoraPremioService;
use App\Models\Produto;
use App\Models\Segurado;
use App\Models\Cotacao;
use Tests\TestCase;

/**
 * Classe de teste destinada a Service CalculadoraPremioService
 */

uses(Tests\TestCase::class);


// =========================================================================
// TESTES SCORE
// =========================================================================
test ('deve calcular premio base corretamente aplicando o score alto', function () {
    //ARRANGE
    $service = new CalculadoraPremioService();

    $produto = new Produto();
    $produto->ramo = 'Outro';
    $produto->parametros_calculo = ['taxa_base' => 5.0];
    
    $segurado = new Segurado();
    $segurado->score = 85;
    
    $dados = [
        'valor_base_risco' => 100000
    ];

    //ACT
    $resultado = $service->calcular($produto, $dados, $segurado);

    //ASSERT
    // 3. ASSERT
    // MATEMÁTICA ESPERADA:
    // Premio Base: 100.000 * 5% = 5.000
    // Fator Multiplicador: 1 + 0 (agravantes) + 0 (acrescimo) - 0 (descontos) - 0.075 (desconto score)
    // Fator = 0.925
    // Resultado Final = 5.000 * 0.925 = 4.625
    expect($resultado)->toBe(4625.00);

});

test ('deve calcular premio base corretamente aplicando o score baixo', function () {
    //ARRANGE
    $service = new CalculadoraPremioService();

    $produto = new Produto();
    $produto->ramo = 'Outro';
    $produto->parametros_calculo = ['taxa_base' => 5.0];
    
    $segurado = new Segurado();
    $segurado->score = 15;
    
    $dados = [
        'valor_base_risco' => 100000
        
    ];

    //ACT
    $resultado = $service->calcular($produto, $dados, $segurado);

    //ASSERT
    // 3. ASSERT
    // MATEMÁTICA ESPERADA:
    // Premio Base: 100.000 * 5% = 5.000
    // Fator Multiplicador: 1 + 0 (agravantes) + 0 (acrescimo) - 0 (descontos) + 0.10 (desconto score)
    // Fator = 1.1
    // Resultado Final = 5.000 * 1.1 = 5.500
    expect($resultado)->toBe(5500.00);

});

// =========================================================================
// RAMO: AUTO
// =========================================================================

// =========================================================================
// RAMO: RESIDENCIAL
// =========================================================================
test('deve calcular agravantes do ramo residencial corretamente', function ($campoAgravante, $valorAgravante, $dadoChave, $dadoValor, $resultadoEsperado) {
    
    $service = new CalculadoraPremioService();

    // Fixamos o score em 50 para não ter nem desconto nem acréscimo de score.
    $segurado = new Segurado(['score' => 50]); 
    
    $produto = new Produto();
    $produto->ramo = 'Residencial';
    // O array de parâmetros recebe a variável dinâmica!
    $produto->parametros_calculo = [
        'taxa_base' => 5.0,
        $campoAgravante => $valorAgravante 
    ];

    $dados = [
        'valor_base_risco' => 100000,
        $dadoChave => $dadoValor // O dado do front-end também é dinâmico!
    ];

    $resultado = $service->calcular($produto, $dados, $segurado);

    expect($resultado)->toBe($resultadoEsperado);

})->with([
    'construcao em madeira' => ['fator_construcao_madeira', 3.0, 'tipo_construcao', 'madeira', 5150.0],
    'casa de veraneio'      => ['fator_uso_veraneio', 5.0, 'uso_residencia', 'veraneio', 5250.0],
    'regiao rural'          => ['fator_regiao_rural', 10.0, 'regiao', 'rural', 5500.0],
]);



// =========================================================================
// RAMO: VIDA
// =========================================================================
        