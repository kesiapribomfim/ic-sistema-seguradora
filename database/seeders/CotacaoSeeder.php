<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cotacao;
use App\Models\Produto;
use App\Models\Segurado;
use App\Models\User;
use App\Services\CalculadoraPremioService;
use Illuminate\Support\Str;

class CotacaoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = Produto::with('coberturas')->get();
        $segurados = Segurado::with(['seguradoPf', 'seguradoPj'])->get();

        if ($produtos->isEmpty() || $segurados->isEmpty()) {
            $this->command->warn('Produtos ou Segurados não encontrados. Cotações não foram geradas.');
            return;
        }

        $calculadora = new CalculadoraPremioService();
        $statusDisponiveis = ['Em Elaboração', 'Enviada ao Cliente', 'Em Subscrição', 'Aceita', 'Recusada'];

        $this->command->info('Gerando Cotações dinâmicas e calculando prêmios reais...');

        for ($i = 0; $i < 20; $i++) {
            $produto = $produtos->random();
            $segurado = $segurados->random();

            $corretor = User::with('filiais')->find($segurado->corretor_id);
            if (!$corretor) continue;

            $filial = $corretor->filiais->where('pivot.perfil_acesso', 'Corretor')->first();
            if (!$filial) continue;

            $dadosEspecificos = $this->gerarDadosEspecificos($produto->ramo);
            $coberturas = $this->gerarCoberturas($produto);

            $dadosFormularioMock = [
                'dados_especificos' => $dadosEspecificos,
                'cobertura_selecionada' => $coberturas,
            ];

            $valorTotal = $calculadora->calcular($produto, $dadosFormularioMock, $segurado);

            $status = ($i < 5) ? 'Aceita' : fake()->randomElement($statusDisponiveis);

            Cotacao::create([
                'segurado_id' => $segurado->id,
                'produto_id' => $produto->id,
                'user_id' => $corretor->id,
                'filial_id' => $filial->id,
                'dados_especificos' => $dadosEspecificos,
                'cobertura_selecionada' => $coberturas,
                'status' => $status,
                'valor_total' => $valorTotal,
                'validade' => now()->addDays(30),
            ]);
        }

        $this->command->info('Cotações geradas com sucesso!');
    }

    // =========================================================
    // FUNÇÕES AUXILIARES DE GERAÇÃO DE DADOS MOCK (JSON)
    // =========================================================

    private function gerarDadosEspecificos(string $ramo): array
    {
        if ($ramo === 'Auto') {
            return [
                'tipo_veiculo' => fake()->randomElement(['carro', 'moto', 'caminhao']),
                'modelo' => fake()->randomElement(['Corolla', 'Civic', 'Onix', 'HB20', 'Hilux']),
                'ano' => fake()->numberBetween(2015, (int) date('Y')),
                'valor_base_risco' => fake()->randomFloat(2, 40000, 250000),
                'sem_placa' => false,
                'placa' => strtoupper(fake()->bothify('???-#?##')),
                'zero' => fake()->boolean(20),
                'kit_gas' => fake()->boolean(10),
                'blindado' => fake()->boolean(5),
                'uso' => fake()->randomElements(['trabalho', 'estudo', 'comercial'], rand(1, 2)),
                'estacionamento' => fake()->randomElement(['garagem', 'rua', 'estacionamento']),
                'seguro_antigo' => fake()->boolean(30),
                'classe_bonus' => fake()->numberBetween(0, 5),
            ];
        }

        if ($ramo === 'Residencial') {
            return [
                'tipo_moradia' => fake()->randomElement(['casa', 'apartamento', 'condominio_horizontal']),
                'uso_residencia' => 'habitavel',
                'tipo_construcao' => fake()->randomElement(['alvenaria', 'madeira']),
                'regiao' => 'urbano',
                'sobre_imovel' => ['proprio'],
                'terreno_baldio' => 'nao',
                'valor_base_risco' => fake()->randomFloat(2, 200000, 1000000),
                'sinistros' => 'nao',
            ];
        }

        if ($ramo === 'Vida') {
            return [
                'valor_base_risco' => fake()->randomFloat(2, 100000, 500000),
                'peso' => fake()->numberBetween(55, 110),
                'altura' => fake()->numberBetween(155, 195),
                'profissao_risco' => fake()->randomElement(['sim', 'nao']),
                'fumante' => fake()->boolean(15),
                'consome_alcool' => fake()->boolean(40),
                'pratica_esportes_radicais' => fake()->boolean(5),
                'possui_doenca_preexistente' => false,
                'beneficiarios_vida' => [
                    [
                        'nome' => fake()->name(),
                        'cpf' => fake()->cpf(false),
                        'parentesco' => 'Cônjuge/Companheiro(a)',
                        'percentual_rateio' => 100
                    ]
                ],
            ];
        }

        return [];
    }

    private function gerarCoberturas(Produto $produto): array
    {
        $coberturas = [];

        foreach ($produto->coberturas as $cobertura) {
            $isObrigatoria = (bool) $cobertura->pivot->obrigatoria;

            $uuid = (string) Str::uuid();

            $coberturas[$uuid] = [
                'cobertura_id' => $cobertura->id,
                'nome_cobertura' => $cobertura->nome,
                'limite_maximo' => $cobertura->pivot->limite_maximo,
                'obrigatoria' => $isObrigatoria,
                'contratada' => $isObrigatoria ? true : fake()->boolean(60),
            ];
        }

        return $coberturas;
    }
}
