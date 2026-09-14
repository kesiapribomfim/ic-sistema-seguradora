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

/**
 * Classe de teste destinada a EstatisticaDashboardService
 */

uses(RefreshDatabase::class);

beforeEach(function () {

    /////////////////////////////////////////////////////////////////////////////////
    // DADOS GLOBAIS
    ////////////////////////////////////////////////////////////////////////////////
    
    // + 5 Segurados Globais
    $ativos = Segurado::factory()->count(5)->create([
        'status' => true,
    ]);
    $inativos = Segurado::factory()->count(3)->create([
        'status' => false,
    ]);
    

    /////////////////////////////////////////////////////////////////////////////////
    // DADOS LOCAIS
    ////////////////////////////////////////////////////////////////////////////////

    //segurados vinculados a apolice local
    // $seguradosApoliceLocal = Segurado::factory()->count(3)->create([
    //     'status' => true,
    // ]);

    // foreach($seguradosApoliceLocal as $segurado) {
    //     $apoliceLocalVigente = [
    //         'segurado_id' => $segurado->id,
    //     ];
    //     return $apoliceLocalVigente;
    // }

    // $seguradoCotacaoLocal = Segurado::factory()->count(3)->create([
    //     'status' => true,
    // ]);





    //Dados da Filial Local (3 corretores)
    $filialLocal = Filial::factory()->created([
        'nome' => 'Ciranna Yuci',
    ]);
    $corretoresLocal = User::factory()->create(3);

    //corretor para relacionar a segurados que não podem estar diretamente vinculados a filial
    User::factory()->create([
        'name' => 'Corretor Flutuante',
    ]);
    //Laço para vincular apolice e segurado a corretores locais
    foreach ($corretoresLocal as $corretor) {
        //Vinculo do corretor a filial
        $corretor->filiais()->attach($filialLocal->id, [
            'perfil_acesso' => 'Corretor',
            'status' => true,
        ]);

        //segurados vinculado ao corretor local -> +3 segurados
        Segurado::factory()->count(3)->create([
            'status' => true,
            'corretor_id' => $corretor->id,
        ]);

        //apolices vinculadas a filial local -> +4 apolices vigentes
        $apoliceLocalVigente = Apolice::factory()->count(4)->create([
            'data_emissao' => '2026-09-01',
            'valor_total'  => 100.00,
            'status'       => 'Vigente',
            'filial_id'    => $filialLocal->id(), //id da filial local
        ]);
    }

    Apolice::factory()->count(30)->create([
        'status' => 'Vigente',
    ]);
    
    Sinistro::factory()->cont(30)->create();

    Segurado::factory()->count(24)->create();

    $this->filialLocal = $filialLocal;


    /////////////////////////////////////////////////////////////////////////////////
    // Variaveis Globais
    ////////////////////////////////////////////////////////////////////////////////
    $this->$filialLocalId = $filialLocal->id;
    
    $this->seguradosGlobais = [
        'ativos' => $ativos,
        'inativos' => $inativos,
        // Dica bônus: já deixa a conta matemática pronta pra facilitar a sua vida no teste!
        'total_ativos' => $ativos->count(),
        'total_inativos' => $inativos->count(),
        'soma_tudo' => $ativos->count() + $inativos->count(),
    ];

});


test('deve contabilizar as estatisticas globalmente de forma correta', function() {
    // 1. Instanciamos a Service
    $service = new EstatisticaDashboardService();

    // 2. Chamamos o método no modo Global (ignorando o array de filiais e passando isGlobal como true)
    $resultado = $service->obterEstatisticas([], true);

    // 3. Como é global, a Service tem que somar TUDO que tem no banco de dados.
    // Nós puxamos as quantidades da mochila ($this) e somamos!
    $quantidadeEsperada = $this->qtdSeguradosGlobais + $this->qtdSeguradosLocais; // 5 + 3 = 8

    // 4. A verificação final
    expect($resultado['total_segurados'])->toBe($quantidadeEsperada);


    //ASSERT
    // expect(count($seguradosQuery))->toBe();
    // expect(count($apolicesVigentesQuery))->toBe();
    // expect(count($sinistrosAnaliseQuery))->toBe();
    // expect($faturamentoTotal)->toBe();
    // expect($custoTotalSinistros)->toBe();
    // expect($sinistralidade)->toBe();

});

test('deve contabilizar as esteticas localmente de forma correta', function() {

    //Dados para estatística local:
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

    $service = new EstatisticaDashboardService;

    //ASSERT
    // expect(count($seguradosQuery))->toBe();
    // expect(count($apolicesVigentesQuery))->toBe();
    // expect(count($sinistrosAnaliseQuery))->tpBe();
    // expect($faturamentoTotal)->toBe();
    // expect($custoTotalSinistros)->toBe();
    // expect($sinistralidade)->toBe();
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
