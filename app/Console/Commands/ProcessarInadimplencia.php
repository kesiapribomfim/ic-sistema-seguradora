<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pagamento;
use App\Models\Apolice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessarInadimplencia extends Command
{
    protected $signature = 'seguradora:processar-inadimplencia';
    protected $description = 'Notifica vencimentos próximos e suspende apólices com parcelas em atraso.';

    public function handle()
    {
        $this->info('Iniciando varredura financeira...');

        $this->notificarVencimentosProximos();
        $this->suspenderApolicesInadimplentes();

        $this->info('Varredura concluída com sucesso!');
    }

    private function notificarVencimentosProximos()
    {
        $diasAviso = 5; 
        $dataAlvo = Carbon::now()->addDays($diasAviso)->toDateString();

        $parcelas = Pagamento::where('status', 'Aberta')
            ->whereDate('data_vencimento', $dataAlvo)
            ->get();

        foreach ($parcelas as $parcela) {
            $this->line("Notificação de vencimento gerada para a parcela {$parcela->id}");
        }
    }

    private function suspenderApolicesInadimplentes()
    {
        $diasTolerancia = 5; 
        $dataLimite = Carbon::now()->subDays($diasTolerancia)->toDateString();

        $parcelasAtrasadas = Pagamento::with('apolice')
            ->where('status', 'Aberta')
            ->whereDate('data_vencimento', '<=', $dataLimite)
            ->get();

        foreach ($parcelasAtrasadas as $parcela) {
            DB::transaction(function () use ($parcela) {
                $parcela->update(['status' => 'Vencida']);

                if ($parcela->apolice->status === 'Vigente') {
                    $parcela->apolice->update(['status' => 'Suspensa por inadimplência']);
                }
            });
            
            $this->error("Apólice {$parcela->apolice->numero_apolice} suspensa por falta de pagamento.");
        }
    }
}