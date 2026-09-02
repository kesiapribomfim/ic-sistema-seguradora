<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Apolice;
use App\Services\RenovaApoliceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessarRenovacoes extends Command
{
    use Queueable;
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'seguradora:processar-renovacoes';

    /**
     * The console command description.
     */
    protected $description = 'Verifica apólices próximas do vencimento (60, 30 e 15 dias) e gera cotação de renovação automática.';

    /**
     * Execute the console command.
     */
    public function handle(RenovaApoliceService $renovaService): void
    {
        $this->info('Iniciando varredura de apólices para renovação...');

        $data60Dias = Carbon::now()->addDays(60)->toDateString();
        $data30Dias = Carbon::now()->addDays(30)->toDateString();
        $data15Dias = Carbon::now()->addDays(15)->toDateString();


        $apolicesParaRenovar = Apolice::where('status', 'Vigente')
            ->whereIn(DB::raw('DATE(data_fim)'), [
                $data60Dias, 
                $data30Dias, 
                $data15Dias
            ])->get();

        if ($apolicesParaRenovar->isEmpty()) {
            $this->line('Nenhuma apólice atingiu o gatilho de renovação hoje.');
            return;
        }

        $this->info("Encontradas {$apolicesParaRenovar->count()} apólices. Iniciando geração de cotações...");

        $sucesso = 0;
        $falha = 0;

        foreach ($apolicesParaRenovar as $apolice) {
            $jaPossuiRenovacao = \App\Models\Cotacao::whereJsonContains('dados_especificos->apolice_origem_id_temporario', $apolice->id)
                ->whereIn('status', ['Em Elaboração', 'Enviada ao Cliente', 'Aceita'])
                ->exists();

            if ($jaPossuiRenovacao) {
                $this->line("A Apólice {$apolice->numero_apolice} já possui uma renovação em andamento. Pulando...");
                continue;
            }

            $this->line("Processando Apólice: {$apolice->numero_apolice}...");
            
            $this->line("Processando Apólice: {$apolice->numero_apolice} (Vencimento: {$apolice->data_fim->format('d/m/Y')})");
            
            $novaCotacao = $renovaService->gerarCotacao($apolice);

            if ($novaCotacao) {
                $sucesso++;
            } else {
                $falha++;
            }

        }

        $this->newLine();
        $this->info("Processamento concluído!");
        $this->info("Renovações geradas: {$sucesso}");
        Log::info("CRON: Renovações geradas: {$sucesso}");
        if ($falha > 0) {
            $this->error("Falhas: {$falha} (Verifique os logs do sistema)");
        }
    }
}