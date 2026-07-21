<?php

namespace App\Mail;

use App\Models\OrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReturnConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly OrderReturn $orderReturn,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdzenie zgłoszenia zwrotu ' . $this->orderReturn->return_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.return-confirmation',
        );
    }
}
