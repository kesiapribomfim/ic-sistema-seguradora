<?php

namespace Tests\Feature;

use App\Services\RenovaApoliceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Models\Apolice;
use App\Models\Cotacao;
use App\Models\Produto;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\expectedArguments;

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

    $apoliceRenova = Apolice::factory()->createQuietly([
        'numero_apolice' => 'AP-TEST456',
    ]);

    $this->apoliceBoa = $apoliceRenova;

    $cotacao = Cotacao::factory()->make(['produto_id' => 1]);

    $apoliceQuebrada = Apolice::factory()->make([
        'id' => 777,
        'segurado_id' => 90219,
    ]);
    
    $apoliceQuebrada->setRelation('cotacao', $cotacao);
    
    $this->apoliceQuebrada = $apoliceQuebrada;

});

describe(
    'LOGS',
        function () {
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
            //Log::info("Nova COTAÇÃO de renovação (#{$novaCotacao->id}) criada em estado 'Em elaboração' a partir da Apólice #{$apolice->numero_apolice}");
                
            test('deve retornar log de cotacao criada com sucesso', function() {
                Log::shouldReceive('info')
                    ->once()
                    ->withArgs(function ($mensagem) {
                        return str_contains($mensagem, "criada em estado 'Em elaboração' a partir da Apólice #AP-TEST456");
                    });
                
                $service = new RenovaApoliceService;
                $resultado = $service->GerarCotacao($this->apoliceBoa);

                expect($resultado->id)->toBeInt()->toBeGreaterThan(0);
            });
});

test ('deve gerar cotacao em elaboracao corretamente segundo a apolice antiga', function () {
    $service = new RenovaApoliceService;
    $resultado = $service->GerarCotacao($this->apoliceBoa);

    expect($resultado->status)->toBe('Em Elaboração');

    expect($resultado->dados_especificos['apolice_origem_id_temporario'])->toBe($this->apoliceBoa->id);

    Queue::assertPushed(\App\Jobs\RenovacaoEmailJob::class, function ($job) {
        return !is_null($job->delay);
    });
});

test('deve disparar erro ao receber apolice quebrada', function() {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($mensagem, ...$context) {
            return str_contains($mensagem, 'Erro ao gerar cotação de renovação da Apólice #777:');
        });
    
    $service = new RenovaApoliceService;
    $resultado = $service->GerarCotacao($this->apoliceQuebrada);

    expect($resultado)->toBeNull();
});






// public function GerarCotacao (Apolice $apolice): ?Cotacao
//     {
//         return DB::transaction(function () use ($apolice){

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