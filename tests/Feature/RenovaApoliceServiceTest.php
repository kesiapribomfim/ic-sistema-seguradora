<?php

namespace Tests\Feature;

use App\Jobs\RenovacaoEmailJob;
use App\Models\Apolice;
use App\Models\Cotacao;
use App\Services\RenovaApoliceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Nette\Schema\Expect;
use Spatie\Permission\Models\Role;

/**
 * Classe de teste destinada a RenovaApoliceService
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
    Queue::fake();

    $cotacaoSemProduto = Cotacao::factory()->make([
        'produto_id' => null,
    ]);
    $apolice = Apolice::factory()->make([
        'numero_apolice' => 'AP-TEST123',
        'data_fim' => now()->format('Y-m-d'),
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
        test('deve retornar log de erro se não houver mais produto id da apólice antiga', function () {
            Log::shouldReceive('error')
                ->once()
                ->with('Falha ao renovar: Produto não encontrado no snapshot da Apólice #AP-TEST123');

            $service = new RenovaApoliceService;
            $resultado = $service->gerarCotacao($this->apolicesSemProduto);

            expect($resultado)->toBeNull();
            Queue::assertNothingPushed();
        });
        test('deve retornar log de cotacao criada com sucesso', function () {
            Log::shouldReceive('info')
                ->once()
                ->withArgs(function ($mensagem) {
                    return str_contains($mensagem, "criada em estado 'Em elaboração' a partir da Apólice #AP-TEST456");
                });

            $service = new RenovaApoliceService;
            $resultado = $service->gerarCotacao($this->apoliceBoa);

            expect($resultado->id)->toBeInt()->toBeGreaterThan(0);
        });
    });

test('deve gerar cotacao em elaboracao corretamente segundo a apolice antiga', function () {
    $service = new RenovaApoliceService;
    $resultado = $service->gerarCotacao($this->apoliceBoa);

    expect($resultado->status)->toBe('Em Elaboração');

    expect($resultado->dados_especificos['apolice_origem_id_temporario'])->toBe($this->apoliceBoa->id);

    expect($resultado->dados_especificos['inicio_vigencia_renovacao'])->toBe($this->apoliceBoa->data_fim->toJSON());

    Queue::assertPushed(RenovacaoEmailJob::class, function ($job) {
        return ! is_null($job->delay);
    });
});

test('deve disparar erro ao receber apolice quebrada', function () {
    Log::shouldReceive('error')
        ->once()
        ->withArgs(function ($mensagem, ...$context) {
            return str_contains($mensagem, 'Erro ao gerar cotação de renovação da Apólice #777:');
        });

    $service = new RenovaApoliceService;
    $resultado = $service->gerarCotacao($this->apoliceQuebrada);

    expect($resultado)->toBeNull();
});
