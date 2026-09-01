<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sinistro;
use App\Models\Apolice;
use App\Models\User;

class SinistroSeeder extends Seeder
{
    public function run(): void
    {
        $apolicesVigentes = Apolice::where('status', 'Vigente')->get();

        if ($apolicesVigentes->isEmpty()) {
            $this->command->warn('Nenhuma Apólice vigente encontrada. Sinistros não foram gerados.');
            return;
        }

        $this->command->info('Criando histórico de sinistros com coberturas reais...');

        $apolicesSistradas = $apolicesVigentes->random(min(15, $apolicesVigentes->count()));

        foreach ($apolicesSistradas as $apolice) {

            $coberturasContratadas = $apolice->snapshot['coberturas'] ?? [];

            if (empty($coberturasContratadas)) continue;

            $coberturaAfetada = fake()->randomElement($coberturasContratadas);
            $nomeCobertura = $coberturaAfetada['nome_cobertura'] ?? 'o bem segurado';
            $limiteCobertura = (float) ($coberturaAfetada['limite_maximo'] ?? 10000);

            $status = fake()->randomElement([
                'Em análise',
                'Aguardando Gestor',
                'Aprovado',
                'Negado',
                'Pago',
                'Encerrado'
            ]);

            $valorIndenizacao = null;
            $valorPago = null;

            // Se o sinistro avançou, calculamos um valor de indenização (nunca maior que o limite da cobertura)
            if (in_array($status, ['Aprovado', 'Pago', 'Encerrado', 'Aguardando Gestor'])) {
                $valorIndenizacao = fake()->randomFloat(2, 500, $limiteCobertura);
            }

            // Se já foi pago ou encerrado, o valor pago é preenchido
            if (in_array($status, ['Pago', 'Encerrado'])) {
                $valorPago = $valorIndenizacao;
            }

            // 3. Criação do Sinistro
            Sinistro::create([
                'apolice_id' => $apolice->id,

                // O sinistro deve ter ocorrido depois do início da vigência da apólice
                'data_hora_ocorrencia' => fake()->dateTimeBetween($apolice->data_inicio, 'now'),

                // Dados do local da ocorrência
                'rua' => fake()->streetName(),
                'numero' => fake()->buildingNumber(),
                'bairro' => fake()->citySuffix(),
                'complemento' => fake()->optional(0.3)->secondaryAddress(),
                'cidade' => fake()->city(),
                'uf' => fake()->stateAbbr(),
                'cep' => fake()->numerify('##.###-###'),

                'descricao' => "Cliente acionou o seguro informando um incidente relacionado a {$nomeCobertura}. Documentação inicial anexada para análise técnica.",

                'coberturas_envolvidas' => [$coberturaAfetada],

                'status' => $status,
                'valor_indenizacao' => $valorIndenizacao,
                'valor_pago' => $valorPago,
            ]);
        }

        $this->command->info('Sinistros gerados com sucesso!');
    }
}
