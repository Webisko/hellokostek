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
            subject: 'Potwierdzenie płatności za zamówienie ' . $this->order->number . ' | hellokostek',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "<div style=\"font-family: sans-serif; color: #1a1a1a; line-height: 1.6;\">"
                . "<h2 style=\"color: #111;\">Dziękujemy za opłacenie zamówienia {$this->order->number}</h2>"
                . "<p>Cześć {$this->order->customer_first_name},</p>"
                . "<p>Otrzymaliśmy płatność za Twoje zamówienie <strong>{$this->order->number}</strong> na kwotę <strong>" . number_format($this->order->total_amount / 100, 2, ',', ' ') . " {$this->order->currency}</strong>.</p>"
                . "<p>Twoja praca jest już przygotowywana do wysyłki w Pracowni Artystycznej hellokostek. O nadaniu paczki poinformujemy Cię w osobnej wiadomości.</p>"
                . "<p style=\"margin-top: 24px;\">Pozdrawiam serdecznie,<br><strong>Maciej Kosteczka • hellokostek</strong></p>"
                . "</div>",
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
