<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Apolice;
use Barryvdh\DomPDF\Facade\Pdf; 

class BoasVindasApoliceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Apolice $apolice;

    /**
     * Create a new message instance.
     */
    public function __construct(Apolice $apolice)
    {
        $this->apolice = $apolice;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua Apólice foi emitida com sucesso!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.boas-vindas-apolice',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $this->apolice->load('pagamentos');
        
        $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $this->apolice]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "apolice-{$this->apolice->numero_apolice}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}