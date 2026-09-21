<?php

namespace App\Console\Commands;

use App\Jobs\EnviarAvisoVencimentoJob;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            EnviarAvisoVencimentoJob::dispatch($parcela);

            $this->line("Notificação de vencimento gerada para a parcela {$parcela->id}");
        }
    }

    private function suspenderApolicesInadimplentes()
    {
        $diasTolerancia = 5;
        $dataLimite = Carbon::now()->subDays($diasTolerancia)->toDateString();

        $parcelasAtrasadas = Pagamento::with('apolice.segurado')
            ->where('status', 'Aberta')
            ->whereDate('data_vencimento', '<=', $dataLimite)
            ->get();

        $apolicesProcessadas = [];

        foreach ($parcelasAtrasadas as $parcela) {

            DB::transaction(function () use ($parcela, &$apolicesProcessadas) {

                $parcela->update(['status' => 'Vencida']);

                if ($parcela->apolice->status === 'Vigente' && ! in_array($parcela->apolice->id, $apolicesProcessadas)) {

                    $parcela->apolice->update(['status' => 'Suspensa por inadimplência']);

                    $segurado = $parcela->apolice->segurado;
                    if ($segurado && $segurado->score > 0) {
                        $novoScore = max(0, $segurado->score - 15);
                        $segurado->score = $novoScore;
                        $segurado->save();
                    }

                    Log::info("CRON: Apólice {$parcela->apolice->numero_apolice} suspensa por inadimplência.");

                    $apolicesProcessadas[] = $parcela->apolice->id;
                }
            });
        }
    }
}
