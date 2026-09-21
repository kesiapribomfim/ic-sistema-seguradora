<?php

use App\Models\Apolice;
use App\Models\Beneficiario;
use App\Models\Cotacao;
use App\Models\Produto;
use App\Services\EmissaoApoliceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

/**
 * Classe de teste destinada a Service EmissaoApoliceService
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
    Queue::fake();

    $produto = Produto::factory()->create([
        'nome' => 'VIDA-TESTE',
        'ramo' => 'Vida',
    ]);

    $cotacao = Cotacao::factory()->createQuietly([
        'valor_total' => 1200.00,
        'produto_id' => $produto->id,
        'dados_especificos' => ['beneficiarios_vida' => [
            ['cpf' => 77777777777, 'nome' => 'beneficiario um', 'data_nascimento' => '2000-01-01', 'percentual_rateio' => 50, 'parentesco' => 'Filho'],
            ['cpf' => 22222222222, 'nome' => 'beneficiario repetido', 'data_nascimento' => '2002-02-02', 'percentual_rateio' => 25, 'parentesco' => 'exemplo'],
        ]],
        'cobertura_selecionada' => [
            'invalidez', 'diarias por incapacidade temporaria', 'despesas medico-hospitalares',
        ],
    ]);

    Beneficiario::create([
        'nome' => 'beneficiario repetido',
        'cpf' => '22222222222',
        'data_nascimento' => '2000-02-02',
    ]);

    $this->cotacao = $cotacao;
    $this->formaPagamento = 'Pix';
    $this->quantidadeParcelas = 10;
    $this->snapshot = ['produto' => ['id' => $cotacao->produto->id, 'nome' => $cotacao->produto->nome], 'coberturas' => $cotacao->cobertura_selecionada];
    $this->beneficiarios = $cotacao->dados_especificos['beneficiarios_vida'];
});

test('deve gerar o numero e valor das parcelas corretamente', function () {
    $service = new EmissaoApoliceService;

    $resultado = $service->emitir($this->cotacao, $this->formaPagamento, $this->quantidadeParcelas);

    expect($resultado->cotacao_id)->toBe($this->cotacao->id);
    expect($resultado->valor_parcela)->toBe(120.00);
    expect($resultado->forma_pagamento)->toBe('Pix');

    $this->assertDatabaseCount('pagamentos', 10);

    $this->assertDatabaseHas('pagamentos', [
        'apolice_id' => $resultado->id,
        'num_parcela' => 1,
        'status' => 'Paga',
    ]);

    $this->assertDatabaseHas('pagamentos', [
        'apolice_id' => $resultado->id,
        'num_parcela' => 2,
        'status' => 'Aberta',
    ]);

});

test('deve gerar apolice corretamente com status vigente, snapshot do produto e numero de apolice valido', function () {
    $service = new EmissaoApoliceService;
    $resultado = $service->emitir($this->cotacao, $this->formaPagamento, $this->quantidadeParcelas);

    expect($resultado->numero_apolice)->toStartWith('AP-');
    expect($resultado->status)->toBe('Vigente');
    expect($resultado->snapshot)->toEqual($this->snapshot);
});

test('deve vincular beneficiarios a apolice de vida corretamente', function () {
    $service = new EmissaoApoliceService;
    $service->emitir($this->cotacao, $this->formaPagamento, $this->quantidadeParcelas);

    $this->assertDatabaseCount('beneficiarios', 2);
    $this->assertDatabaseHas('beneficiarios', [
        'cpf' => $this->beneficiarios[0]['cpf'],
        'nome' => $this->beneficiarios[0]['nome'],
    ]);
    $this->assertDatabaseHas('beneficiarios', [
        'cpf' => $this->beneficiarios[1]['cpf'],
        'nome' => $this->beneficiarios[1]['nome'],
    ]);
});

test('deve gerar corretamente uma apolice sem numero de parcelas e beneficiarios', function () {
    $cotacaoSemParcelas = Cotacao::factory()->createQuietly([
        'valor_total' => 1000.0,
    ]);

    $service = new EmissaoApoliceService;

    $resultado = $service->emitir($cotacaoSemParcelas, $this->formaPagamento, 0);

    expect($resultado->cotacao_id)->toBe($cotacaoSemParcelas->id);
    $this->assertDatabaseHas('pagamentos', [
        'apolice_id' => $resultado->id,
        'num_parcela' => 1,
        'status' => 'Paga',
    ]);

});

test('deve retirar id da apolice antiga dos dados especificos da cotacao', function () {
    $apoliceAntiga = Apolice::factory()->createQuietly();

    $cotacaoRenovacao = Cotacao::factory()->createQuietly([
        'dados_especificos' => ['apolice_origem_id_temporario' => $apoliceAntiga->id],
    ]);

    $service = new EmissaoApoliceService;

    $resultado = $service->emitir($cotacaoRenovacao, $this->formaPagamento, $this->quantidadeParcelas);

    expect($resultado->apolice_origem_id)->toBe($apoliceAntiga->id);
    expect($resultado->dados_bem_assegurado)->not->toHaveKey('apolice_origem_id_temporario');
});

test('deve ignorar beneficiarios com nome ou cpf em branco', function () {

    $cotacaoIncompleta = Cotacao::factory()->createQuietly([
        'valor_total' => 1200.00,
        'dados_especificos' => ['beneficiarios_vida' => [
            ['cpf' => '', 'nome' => 'Sem CPF', 'percentual_rateio' => 50, 'parentesco' => 'Irmão'],
            ['cpf' => '12345678900', 'nome' => '', 'percentual_rateio' => 50, 'parentesco' => 'Irmão'],
            ['cpf' => '99999999999', 'nome' => 'Valido', 'percentual_rateio' => 100, 'parentesco' => 'Irmão'],
        ]],
    ]);

    $service = new EmissaoApoliceService;

    $service->emitir($cotacaoIncompleta, 'Pix', 1);

    $this->assertDatabaseCount('beneficiarios', 2);
    $this->assertDatabaseHas('beneficiarios', [
        'cpf' => '99999999999',
        'nome' => 'Valido',
    ]);
});

test('deve emitir apolice de renovacao com data de inicio futura baseada na cotacao', function () {
    $dataFutura = '2026-12-01';
    
    $cotacaoRenovacao = Cotacao::factory()->createQuietly([
        'valor_total' => 1000.0,
        'dados_especificos' => [
            'inicio_vigencia_renovacao' => $dataFutura
        ],
    ]);

    $service = new EmissaoApoliceService;

    $resultado = $service->emitir($cotacaoRenovacao, $this->formaPagamento, $this->quantidadeParcelas);

    expect($resultado->data_inicio->format('Y-m-d'))->toBe($dataFutura);
    
    expect($resultado->data_fim->format('Y-m-d'))->toBe('2027-12-01');
    
    expect($resultado->dados_bem_assegurado)->not->toHaveKey('inicio_vigencia_renovacao');
});
