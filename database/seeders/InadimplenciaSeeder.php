<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pagamento;
use Carbon\Carbon;

class InadimplenciaSeeder extends Seeder
{
    public function run(): void
    {
        // Cenário 1: Parcela vencendo daqui a 5 dias (Vai disparar o E-mail)
        Pagamento::factory()->create([
            'status' => 'Aberta',
            'data_vencimento' => Carbon::now()->addDays(5),
            'tipo_movimentacao' => 'Recebimento',
        ]);

        // Cenário 2: Parcela atrasada há 6 dias (Passou a tolerância, vai Suspender a apólice!)
        Pagamento::factory()->create([
            'status' => 'Aberta',
            'data_vencimento' => Carbon::now()->subDays(6),
            'tipo_movimentacao' => 'Recebimento',
        ]);
    }
}