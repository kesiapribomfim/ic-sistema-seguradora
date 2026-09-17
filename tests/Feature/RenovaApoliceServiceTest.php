<?php

namespace Tests\Feature;

use App\Services\RenovaApoliceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Models\Apolice;
use App\Models\Cotacao;
use App\Models\Produto;
use Illuminate\Support\Facades\Log;

/**
 * Classe de teste destinada a RenovaApoliceService
 */

uses(RefreshDatabase::class);


beforeEach(function() {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
    Queue::fake();

    $cotacaoSemProduto = Cotacao::factory()->make([
        'produto_id' => null,
    ]);
    $apolice = Apolice::factory()->make([
        'numero_apolice' => 'AP-TEST123',
        'data_fim'=> now()->format('Y-m-d'),
    ]);
    $apolice->setRelation('cotacao', $cotacaoSemProduto);

    $this->apolicesSemProduto = $apolice;

});

test('deve retornar log de erro se não houver mais produto id da apólice antiga', function() {
    //Teste de log
    //try {
    //     $produtoId = $apolice->cotacao->produto_id ?? null;
    //         if (!$produtoId) {
    //             Log::error("Falha ao renovar: Produto não encontrado no snapshot da Apólice #{$apolice->numero_apolice}");
    //             return null;
    //         }
    Log::shouldReceive('error')
                ->once() 
                ->with("Falha ao renovar: Produto não encontrado no snapshot da Apólice #AP-TEST123");

    $service = new RenovaApoliceService;
    $resultado = $service->GerarCotacao($this->apolicesSemProduto);

    expect($resultado)->toBeNull();
    Queue::assertNothingPushed();
    
});






// public function GerarCotacao (Apolice $apolice): ?Cotacao
//     {
//         return DB::transaction(function () use ($apolice){
//             try {

//                 $produtoId = $apolice->cotacao->produto_id ?? null;

//                 if (!$produtoId) {
//                     Log::error("Falha ao renovar: Produto não encontrado no snapshot da Apólice #{$apolice->numero_apolice}");
//                     return null;
//                 }

//                 $dadosEspecificos = $apolice->dados_bem_assegurado ?? [];
//                 $dadosEspecificos['apolice_origem_id_temporario'] = $apolice->id;

//                 $novaCotacao = Cotacao::create([
//                     'segurado_id'           => $apolice->segurado_id,
//                     'user_id'               => $apolice->user_id, // Corretor responsável
//                     'filial_id'             => $apolice->filial_id,
//                     'produto_id'            => $apolice->cotacao->produto_id,
//                     'cobertura_selecionada' => $apolice->snapshot['coberturas'] ?? [],
//                     'dados_especificos'     => $dadosEspecificos,
//                     'status'                => 'Em Elaboração', 
//                     'validade'              => Carbon::now()->addDays(30),

//                     'valor_total'           => $apolice->valor_total, 
//                 ]);

//                 $atrasoEmSegundos = rand(5, 15); 
//                 //delay
//                 RenovacaoEmailJob::dispatch($apolice, $novaCotacao)->delay(now()->addSeconds($atrasoEmSegundos));

//                 Log::info("Nova COTAÇÃO de renovação (#{$novaCotacao->id}) criada em estado 'Em elaboração' a partir da Apólice #{$apolice->numero_apolice}");
                
//                 return $novaCotacao;

//             } catch (\Exception $e) {
//                 Log::error("Erro ao gerar cotação de renovação da Apólice #{$apolice->id}: " . $e->getMessage());
//                 return null;
//             }
//         });
//     }