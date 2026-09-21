<?php

namespace App\Mail;

use App\Models\Cotacao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CotacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Cotacao $cotacao;

    /**
     * Create a new message instance.
     */
    public function __construct(Cotacao $cotacao)
    {
        $this->cotacao = $cotacao;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Proposta de Cotação de Seguro',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.cotacao-aceite',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
