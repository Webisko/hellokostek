<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaidCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Potwierdzenie płatności za zamówienie ' . $this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<p>Witaj {$this->order->customer_first_name},</p>"
                . "<p>Dziękujemy! Otrzymaliśmy płatność za Twoje zamówienie <strong>{$this->order->number}</strong>.</p>"
                . "<p>W załączniku przesyłamy fakturę potwierdzającą zakup.</p>"
                . "<p>Pozdrawiamy,<br>Sklep internetowy</p>",
        );
    }

    public function attachments(): array
    {
        $invoice = $this->order->invoices()->latest()->first();

        if ($invoice && $invoice->pdf_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($invoice->pdf_path)) {
            return [
                \Illuminate\Mail\Mailables\Attachment::fromPath(
                    \Illuminate\Support\Facades\Storage::disk('local')->path($invoice->pdf_path)
                )->as('Faktura_' . str_replace('/', '_', $invoice->number) . '.pdf')
                ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
