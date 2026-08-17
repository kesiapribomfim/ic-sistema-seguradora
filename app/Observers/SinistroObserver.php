<?php

namespace App\Observers;

use App\Models\Sinistro;
use App\Jobs\RecalcularScoreRiscoJob;

class SinistroObserver
{
    /**
     * Handle the Sinistro "created" event.
     */
    public function created(Sinistro $sinistro): void
    {
        // 1. Cria automaticamente a primeira linha da timeline
        $sinistro->movimentacoes()->create([
            // O fallback (?? 1) evita que o sistema quebre caso um sinistro seja criado via Seeder/Terminal
            'user_id' => auth()->id() ?? 1, 
            'data_hr_movimentacao' => now(),
            'acao_realizada' => 'Abertura',
            'descricao' => 'Abertura automática do sinistro. Relato inicial: ' . $sinistro->descricao,
        ]);
    }

    /**
     * Handle the Sinistro "updated" event.
     */
    public function updated(Sinistro $sinistro): void
    {
        // Verifica se a coluna 'status' foi alterada nesta exata requisição
        if ($sinistro->wasChanged('status')) {
            
            // Se o novo status for um indicativo de que o sinistro foi consumado
            if (in_array($sinistro->status, ['Aprovado', 'Pago', 'Encerrado'])) {
                
                // Pegamos o ID do segurado através da relação com a apólice
                $seguradoId = $sinistro->apolice->segurado_id;

                // =========================================================================
                // CHECKLIST DE INFRAESTRUTURA PARA JOBS / FILAS (LEMBRETE PARA O FUTURO)
                // =========================================================================
                // Para que o comando ::dispatch() abaixo funcione perfeitamente, 
                // você precisará garantir que 3 coisas estejam configuradas no projeto:
                //
                // 1. A JOB CRIADA: Rodar `php artisan make:job RecalcularScoreRiscoJob`
                // 2. O .ENV CONFIGURADO: A variável QUEUE_CONNECTION no seu arquivo .env 
                //    deve estar como `database` (para ambiente local de dev).
                // 3. A TABELA DE FILAS: Se usar o driver 'database', você precisa rodar 
                //    `php artisan queue:table` e depois `php artisan migrate` para 
                //    criar a tabela que vai armazenar as tarefas pendentes.
                // 4. O WORKER RODANDO: O Laravel não executa a fila sozinho no dev. 
                //    Você precisará abrir uma aba separada no terminal e deixar o 
                //    comando `php artisan queue:listen` (ou queue:work) rodando. 
                //    É ele quem "suga" as tarefas da fila e as executa em background.
                // =========================================================================

                // Despacha a tarefa para a fila, liberando a tela do usuário imediatamente
                //RecalcularScoreRiscoJob::dispatch($seguradoId);
            }
        }
    }

    // public function created(Sinistro $sinistro): void
    // {
    //     if (in_array($sinistro->status, ['Aprovado', 'Pago', 'Encerrado'])) {
    //         $seguradoId = $sinistro->apolice->segurado_id;
    //         RecalcularScoreRiscoJob::dispatch($seguradoId);
    //     }
    // }

    /**
     * Handle the Sinistro "deleted" event.
     */
    public function deleted(Sinistro $sinistro): void
    {
        //
    }

    /**
     * Handle the Sinistro "restored" event.
     */
    public function restored(Sinistro $sinistro): void
    {
        //
    }

    /**
     * Handle the Sinistro "force deleted" event.
     */
    public function forceDeleted(Sinistro $sinistro): void
    {
        //
    }
}
