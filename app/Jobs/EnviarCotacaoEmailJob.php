<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Cotacao;
use Illuminate\Support\Facades\Mail;

class EnviarCotacaoEmailJob implements ShouldQueue
{
    use Queueable;

    public Cotacao $cotacao;

    /**
     * Create a new job instance.
     */
    public function __construct(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->cotacao->segurado->email)
            ->send(new \App\Mail\CotacaoMail($this->cotacao));
    }
}
