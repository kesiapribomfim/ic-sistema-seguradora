<?php

namespace Tests\Feature;

use App\Filament\Resources\SeguradoResource;
use App\Services\EstatisticaDashboardService;
use App\Models\Segurado;
use App\Models\Apolice;
use App\Models\Filial;
use App\Models\User;
use App\Models\Sinistro;
use App\Models\Cotacao;
use Illuminate\Support\Facades\Log;
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
    foreach ($segurados as $segurado) {
        $cotacao = Cotacao::factory()->create([
            'segurado_id' => $segurado->id,
        ]);

        $apoliceVigente = Apolice::factory()->create([
            'segurado_id'  => $segurado->id,
            'cotacao_id'   => $cotacao->id,
            'data_emissao' => '2026-09-01',
            'valor_total'  => 100.00,
            'status'       => 'Vigente',
        ]);
        
        $faturamentoTotal += $apoliceVigente->valor_total;
    }

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


    //segurados vinculado ao corretor local -> +5 segurados
    $seguradosLocais = Segurado::factory()->count(5)->create([
        'corretor_id' => $corretorLocal->id,
    ]);

    $faturamentoTotalLocal = 0.0;

    $seguradosApoliceLocal = Segurado::factory()->create();

    foreach ($seguradosApoliceLocal as $segurado) {
        $cotacao = Cotacao::factory()->create([
            'segurado_id' => $segurado->id,
        ]);
        $apoliceLocalVigente = Apolice::factory()->count(5)->create([
        'data_emissao' => '2026-09-01',
        'valor_total'  => 100.00,
        'status'       => 'Vigente',
        'filial_id'    => $filialLocal->id,
    ]);
        $faturamentoTotalLocal += $apoliceLocalVigente->valor_total;
    }

 


    /////////////////////////////////////////////////////////////////////////////////
    // Variaveis
    ////////////////////////////////////////////////////////////////////////////////
    
    //retorno dos dados globais
    $this->dadosGlobais = [
        'total_segurados'       => $segurados->count() + $seguradosLocais->count() + $seguradosApoliceLocal->count(),
        'apolices_vigentes'     => $apoliceVigente->count() + $apoliceLocalVigente->count(),
        'sinistros_analise'     => 0,
        'faturamento_total'     => $faturamentoTotal + $faturamentoTotalLocal,
        'custo_total_sinistros' => 0,
        'sinistralidade'        => 0,
    ];

    //retorno dos dados locais
    $this->filialLocalId = $filialLocal->id;

    $this->dadosLocais = [
        'total_segurados'       => $seguradosLocais->count() + $seguradosApoliceLocal->count(),
        'apolices_vigentes'     => $apoliceLocalVigente->count(),
        'sinistros_analise'     => 0,
        'faturamento_total'     => $faturamentoTotalLocal,
        'custo_total_sinistros' => 0,
        'sinistralidade'        => 0,
    ];

});


test('deve contabilizar as estatisticas globalmente de forma correta', function() {
    
$service = new EstatisticaDashboardService();

    $resultado = $service->obterEstatisticas([], true);


    expect($resultado)->toEqual($this->dadosGlobais);

});

test('deve contabilizar as esteticas localmente de forma correta', function() {

//     //LINHAS DE TESTE DA SERVICE:
//     //         if (!$isGlobal && !empty($filiaisIds)) {
//     //             $seguradosQuery->where(function ($q) use ($filiaisIds) {
//     //                 $q->whereHas('corretor.filiais', fn($q2) => $q2->whereIn('filiais.id', $filiaisIds))
//     //                     ->orWhereHas('apolices', fn($q3) => $q3->whereIn('filial_id', $filiaisIds))
//     //                     ->orWhereHas('cotacoes', fn($q4) => $q4->whereIn('filial_id', $filiaisIds))
//     //                     ->orWhereHas('user.filiais', fn($q5) => $q5->whereIn('filiais.id', $filiaisIds));
//     //             });

//     //             $apolicesVigentesQuery->whereIn('filial_id', $filiaisIds);
//     //             $faturamentoQuery->whereIn('filial_id', $filiaisIds);

//     //             $sinistrosAnaliseQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
//     //             $custoSinistrosQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
//     //         }
    $service = new EstatisticaDashboardService;

    $resultado = $service->obterEstatisticas([$this->filialLocalId], false);


    expect($resultado)->toEqual($this->dadosLocais);

});




//  public function obterEstatisticas(array $filiaisIds = [], bool $isGlobal = false): array
//     {
//         $anoAtual = Carbon::now()->year;

//         $seguradosQuery = Segurado::query();
//         $apolicesVigentesQuery = Apolice::where('status', 'Vigente');
//         $sinistrosAnaliseQuery = Sinistro::where('status', 'Em análise');

//         $faturamentoQuery = Apolice::whereNotIn('status', ['Cancelada', 'Em Elaboração'])
//             ->whereYear('data_emissao', $anoAtual);

//         $custoSinistrosQuery = Sinistro::whereIn('status', ['Aprovado', 'Pago', 'Encerrado'])
//             ->whereYear('data_hora_ocorrencia', $anoAtual);

//         if (!$isGlobal && !empty($filiaisIds)) {
//             $seguradosQuery->where(function ($q) use ($filiaisIds) {
//                 $q->whereHas('corretor.filiais', fn($q2) => $q2->whereIn('filiais.id', $filiaisIds))
//                     ->orWhereHas('apolices', fn($q3) => $q3->whereIn('filial_id', $filiaisIds))
//                     ->orWhereHas('cotacoes', fn($q4) => $q4->whereIn('filial_id', $filiaisIds))
//                     ->orWhereHas('user.filiais', fn($q5) => $q5->whereIn('filiais.id', $filiaisIds));
//             });

//             $apolicesVigentesQuery->whereIn('filial_id', $filiaisIds);
//             $faturamentoQuery->whereIn('filial_id', $filiaisIds);

//             $sinistrosAnaliseQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
//             $custoSinistrosQuery->whereHas('apolice', fn($q) => $q->whereIn('filial_id', $filiaisIds));
//         }

//         $faturamentoTotal = $faturamentoQuery->sum('valor_total');
//         $custoTotalSinistros = $custoSinistrosQuery->sum('valor_indenizacao');

//         $sinistralidade = $faturamentoTotal > 0 ? ($custoTotalSinistros / $faturamentoTotal) * 100 : 0;

//         return [
//             'total_segurados'       => $seguradosQuery->count(),
//             'apolices_vigentes'     => $apolicesVigentesQuery->count(),
//             'sinistros_analise'     => $sinistrosAnaliseQuery->count(),
//             'faturamento_total'     => $faturamentoTotal,
//             'custo_total_sinistros' => $custoTotalSinistros,
//             'sinistralidade'        => $sinistralidade,
//         ];
//     }
