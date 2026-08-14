<?php

namespace App\Jobs;
use App\Models\Apolice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $this->apolice]);

        //logica para chamar classe mail
        Mail::to($this->apolice->segurado->email)->send(new \App\Mail\BoasVindasApoliceMail($this->apolice, $pdf->output()));
    }
}
