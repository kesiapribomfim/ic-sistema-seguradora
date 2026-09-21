<?php

namespace Tests\Feature;

use App\Services\EstatisticaDashboardService;
use App\Models\Segurado;
use App\Models\Apolice;
use App\Models\Filial;
use App\Models\User;
use App\Models\Sinistro;
use App\Models\Cotacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

/**
 * Classe de teste destinada a EstatisticaDashboardService
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    //perfil
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
    Mail::fake();
    
    /////////////////////////////////////////////////////////////////////////////////
    // DADOS GLOBAIS
    ////////////////////////////////////////////////////////////////////////////////
    
    // + 5 Segurados Globais
    $segurados = Segurado::factory()->count(5)->create([
        'status' => true,
    ]);

    //+5 apolices vigentes, + R$500 valor faturamento total
    $faturamentoTotal = 0.0;
    $qtdApoliceVigenteTotal = 0;


    $custoSinistros = 0.0;
    $qtdSinistrosEmAnaliseTotal = 0;

    foreach ($segurados as $segurado) {
        $cotacao = Cotacao::factory()->create([
            'segurado_id' => $segurado->id,
        ]);

        $apoliceVigente = Apolice::factory()->create([
            'segurado_id'  => $segurado->id,
            'cotacao_id'   => $cotacao->id,
            'data_emissao' => now()->format('Y-m-d'),
            'valor_total'  => 100.00,
            'status'       => 'Vigente',
        ]);
        $faturamentoTotal += $apoliceVigente->valor_total;
        $qtdApoliceVigenteTotal ++;

        Sinistro::factory()->createQuietly([
            'apolice_id'           => $apoliceVigente->id,
            'status'               => 'Em análise',
            'valor_indenizacao'    => 0.0,
            'data_hora_ocorrencia' => now()->format('Y-m-d H:i:s'),

        ]);
        $qtdSinistrosEmAnaliseTotal ++;

        $sinistrosPago = Sinistro::factory()->createQuietly([
            'apolice_id'        => $apoliceVigente->id,
            'status'            => 'Pago',
            'valor_indenizacao' => 50.0,
            'data_hora_ocorrencia' => now()->format('Y-m-d H:i:s'),
        ]);

        $custoSinistros += $sinistrosPago->valor_indenizacao;
        
    }

        //apolices canceladas
        Apolice::factory()->create([
            'segurado_id'  => $segurado->id,
            'cotacao_id'   => $cotacao->id,
            'data_emissao' => now()->format('Y-m-d'),
            'valor_total'  => 100.0,
            'status'       => 'Cancelada',
        ]);

        //apolice antiga
        $apoliceAntiga = Apolice::factory()->create([
            'segurado_id' => $segurado->id,
            'cotacao_id'  => $cotacao->id,
            'data_emissao' => '2025-10-01',
            'valor_total'  => 100.0,
            'status'       => 'Vigente',
        ]);

        //sinistro negado
        Sinistro::factory()->createQuietly([
            'apolice_id'        => $apoliceAntiga->id,
            'status'            => 'Negado',
            'valor_indenizacao' => 50.0,
            'data_hora_ocorrencia' => now()->format('Y-m-d H:i:s'),
        ]);

    /////////////////////////////////////////////////////////////////////////////////
    // DADOS LOCAIS
    ////////////////////////////////////////////////////////////////////////////////
    $filialLocal = Filial::factory()->create([
        'nome' => 'Local Teste'
    ]);

    $corretorLocal = User::factory()->create();
    $corretorLocal->filials()->attach($filialLocal->id, [
            'perfil_acesso' => 'Corretor'
    ]);

    //+5 segurados (vinculados a filial via corretor_id)
    $seguradosLocais = Segurado::factory()->count(5)->create([
        'corretor_id' => $corretorLocal->id,
        'status'      => true,
    ]);

    $userLocal = User::factory()->create();
    $userLocal->filiais()->attach($filialLocal->id, [
        'perfil_acesso' => 'Cliente'
    ]);
    //+1 segurado (vinculado a perfil de user local)
    Segurado::factory()->create(['user_id' => $userLocal->id, 'status' => true]);

    $seguradoCotacaoLocal = Segurado::factory()->create(['status' => true]);
    Cotacao::factory()->create([
        'segurado_id' => $seguradoCotacaoLocal->id,
        'filial_id'   => $filialLocal->id,
    ]);

    $faturamentoLocal = 0.0;
    $qtdApoliceVigenteLocal = 0;

    $custoSinistrosLocal = 0.0;
    $qtdSinistrosEmAnaliseLocal = 0;

    //+5 segurados (com apolices locais vinculadas a eles)
    $seguradosApoliceLocal = Segurado::factory()->count(5)->create(['status' => true]);

    foreach ($seguradosApoliceLocal as $segurado) {
        $cotacao = Cotacao::factory()->create([
            'segurado_id' => $segurado->id,
        ]);
        $apoliceLocalVigente = Apolice::factory()->create([
            'segurado_id'  => $segurado->id,
            'cotacao_id'   => $cotacao->id,
            'data_emissao' => now()->format('Y-m-d'),
            'valor_total'  => 100.00,
            'status'       => 'Vigente',
            'filial_id'    => $filialLocal->id,
        ]);
        $faturamentoLocal += $apoliceLocalVigente->valor_total;
        $qtdApoliceVigenteLocal ++;

        Sinistro::factory()->createQuietly([
            'apolice_id'           => $apoliceLocalVigente->id,
            'status'               => 'Em análise',
            'valor_indenizacao'    => 0.0,
            'data_hora_ocorrencia' => now()->format('Y-m-d H:i:s'),

        ]);
        $qtdSinistrosEmAnaliseLocal ++;

        $sinistrosPago = Sinistro::factory()->createQuietly([
            'apolice_id'        => $apoliceLocalVigente->id,
            'status'            => 'Pago',
            'valor_indenizacao' => 50.0,
            'data_hora_ocorrencia' => now()->format('Y-m-d H:i:s'),  
        ]);

        $custoSinistrosLocal += $sinistrosPago->valor_indenizacao;
    }

    /////////////////////////////////////////////////////////////////////////////////
    // Variaveis
    ////////////////////////////////////////////////////////////////////////////////
    
    //retorno dos dados globais
    $faturamentoTotalGeral = $faturamentoTotal + $faturamentoLocal;
    $custoSinistrosTotal = $custoSinistros + $custoSinistrosLocal;

    $this->dadosGlobais = [
        'total_segurados'       => $segurados->count() + $seguradosLocais->count() + $seguradosApoliceLocal->count() + 2,
        'apolices_vigentes'     => $qtdApoliceVigenteLocal + $qtdApoliceVigenteTotal + 1,
        'sinistros_analise'     => $qtdSinistrosEmAnaliseTotal + $qtdSinistrosEmAnaliseLocal,
        'faturamento_total'     => $faturamentoTotalGeral,
        'custo_total_sinistros' => $custoSinistrosTotal,
        'sinistralidade'        => $faturamentoTotalGeral > 0 ? ($custoSinistrosTotal / $faturamentoTotalGeral) * 100 : 0,
    ];

    //retorno dos dados locais
    $this->filialLocalId = $filialLocal->id;

    $this->dadosLocais = [
        'total_segurados'       => $seguradosLocais->count() + $seguradosApoliceLocal->count() + 2,
        'apolices_vigentes'     => $qtdApoliceVigenteLocal,
        'sinistros_analise'     => $qtdSinistrosEmAnaliseLocal,
        'faturamento_total'     => $faturamentoLocal,
        'custo_total_sinistros' => $custoSinistrosLocal,
        'sinistralidade'        => $faturamentoLocal > 0 ? ($custoSinistrosLocal / $faturamentoLocal) * 100 : 0,
    ];

});


test('deve contabilizar as estatisticas globalmente de forma correta', function() {
    $service = new EstatisticaDashboardService();
    $resultado = $service->obterEstatisticas([], true);

    expect($resultado)->toEqual($this->dadosGlobais);
});

test('deve contabilizar as esteticas localmente de forma correta', function() {
    $service = new EstatisticaDashboardService;
    $resultado = $service->obterEstatisticas([$this->filialLocalId], false);

    expect($resultado)->toEqual($this->dadosLocais);
});

test('deve retornar sinistralidade zero se nao houver faturamento', function() {
    
    $filial = Filial::factory()->create();
    
    $service = new EstatisticaDashboardService();
    $resultado = $service->obterEstatisticas([$filial->id], false);

    expect($resultado['sinistralidade'])->toBe(0.0);
    expect($resultado['faturamento_total'])->toBe(0.0);
});

test('deve desconsiderar segurados inativos', function() {
    //segurados inativos
    Segurado::factory()->count(10)->create(['status'=> false]);
    $service = new EstatisticaDashboardService();
    $resultado = $service->obterEstatisticas([], true);

    expect($resultado)->toEqual($this->dadosGlobais);
});