<?php

namespace App\Jobs;

use App\Models\Pagamento;
use App\Mail\AvisoVencimentoMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarAvisoVencimentoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pagamento;

    public function __construct(Pagamento $pagamento)
    {
        $this->pagamento = $pagamento;
    }

    public function handle(): void
    {
        // Pega o e-mail do segurado através dos relacionamentos
        $emailSegurado = $this->pagamento->apolice->segurado->email;
        
        Mail::to($emailSegurado)->send(new AvisoVencimentoMail($this->pagamento));
    }
}