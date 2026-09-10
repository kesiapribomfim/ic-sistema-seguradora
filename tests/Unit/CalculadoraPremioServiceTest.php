<?php

use App\Services\CalculadoraPremioService;
use App\Models\Produto;
use App\Models\Segurado;
use Tests\TestCase;

/**
 * Classe de teste destinada a Service CalculadoraPremioService
 */

uses(Tests\TestCase::class);

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