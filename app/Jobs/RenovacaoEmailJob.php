<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Apolice;
use App\Models\Cotacao;
use Illuminate\Support\Facades\Mail;


class RenovacaoEmailJob implements ShouldQueue
{
    use Queueable;

    public Apolice $apolice;
    public Cotacao $novaCotacao;

    /**
     * Create a new job instance.
     */
    public function __construct(Apolice $apolice, Cotacao $novaCotacao)
    {
        $this->apolice = $apolice;
        $this->novaCotacao = $novaCotacao;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->apolice->user->email)
            ->send(new \App\Mail\RenovacaoMail($this->apolice, $this->novaCotacao));
    }
}
