<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShippingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Twoje zamówienie ' . $this->order->number . ' zostało wysłane!',
        );
    }

    public function content(): Content
    {
        $carrier = $this->order->carrier ?? 'Kurier';
        $trackingNumber = $this->order->tracking_number ?? '-';

        return new Content(
            htmlString: "<p>Witaj {$this->order->customer_first_name},</p>"
                . "<p>Z przyjemnością informujemy, że Twoje zamówienie <strong>{$this->order->number}</strong> zostało przekazane przewoźnikowi!</p>"
                . "<p><strong>Szczegóły dostawy:</strong></p>"
                . "<ul>"
                . "<li>Przewoźnik: {$carrier}</li>"
                . "<li>Numer śledzenia: <strong>{$trackingNumber}</strong></li>"
                . "</ul>"
                . "<p>Możesz śledzić swoją przesyłkę na stronie przewoźnika.</p>"
                . "<p>Dziękujemy za zakupy w naszym sklepie!</p>",
        );
    }
}
