<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Apolice;

class BoasVindasApoliceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Apolice $apolice;
    public $pdfContent;

    /**
     * Create a new message instance.
     */
    public function __construct(Apolice $apolice, $pdfContent)
    {
        $this->apolice = $apolice;
        $this->pdfContent = $pdfContent;
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
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "apolice-{$this->apolice->numero_apolice}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
