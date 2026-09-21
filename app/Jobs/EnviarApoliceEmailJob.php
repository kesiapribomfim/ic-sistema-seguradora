<?php

namespace App\Jobs;

use App\Mail\BoasVindasApoliceMail;
use App\Models\Apolice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EnviarApoliceEmailJob implements ShouldQueue
{
    use Queueable;

    public Apolice $apolice;

    /**
     * Create a new job instance.
     */
    public function __construct(Apolice $apolice)
    {
        $this->apolice = $apolice;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->apolice->segurado->email)
            ->send(new BoasVindasApoliceMail($this->apolice));
    }
}
