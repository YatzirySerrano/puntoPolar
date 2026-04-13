<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompraExitosaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Pedido $pedido)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmación de compra #'.$this->pedido->folio,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compra-exitosa',
            with: [
                'pedido' => $this->pedido,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
