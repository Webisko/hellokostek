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
            subject: 'Twoje zamówienie ' . $this->order->number . ' zostało wysłane! | hellokostek',
        );
    }

    public function content(): Content
    {
        $carrier = $this->order->carrier ?: ($this->order->shipping_method_name ?: 'Kurier / Paczkomat');
        $trackingNumber = $this->order->tracking_number ?: null;
        $deliveryPoint = data_get($this->order->metadata, 'delivery_point');
        $pointLabel = !empty($deliveryPoint['id'])
            ? (($deliveryPoint['name'] ?? $deliveryPoint['id']) . (!empty($deliveryPoint['address']) ? ' — ' . $deliveryPoint['address'] . ', ' . ($deliveryPoint['city'] ?? '') : ''))
            : null;

        $detailsHtml = "<li>Sposób dostawy / przewoźnik: <strong>" . e($carrier) . "</strong></li>";
        if ($trackingNumber) {
            $detailsHtml .= "<li>Numer przesyłki (śledzenia): <strong>" . e($trackingNumber) . "</strong></li>";
        }
        if ($pointLabel) {
            $detailsHtml .= "<li>Punkt odbioru: <strong>" . e($pointLabel) . "</strong></li>";
        }

        return new Content(
            htmlString: "<div style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1a1a; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #eee; border-radius: 16px;\">"
                . "<h2 style=\"font-size: 22px; color: #111; margin: 0 0 12px 0;\">Twoje zamówienie {$this->order->number} zostało wysłane!</h2>"
                . "<p style=\"margin: 0 0 16px 0;\">Cześć {$this->order->customer_first_name},</p>"
                . "<p style=\"margin: 0 0 16px 0;\">Z przyjemnością informujemy, że Twoje zamówienie <strong>{$this->order->number}</strong> opuściło Pracownię Artystyczną <strong>hellokostek</strong> i zostało nadane.</p>"
                . "<div style=\"background-color: #faf9f6; border: 1px solid #e7e5e4; border-radius: 12px; padding: 16px; margin-bottom: 20px;\">"
                . "<p style=\"margin: 0 0 8px 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; color: #57534e; font-weight: bold;\">Szczegóły przesyłki</p>"
                . "<ul style=\"margin: 0; padding-left: 20px; font-size: 14px;\">{$detailsHtml}</ul>"
                . "</div>"
                . "<p style=\"font-size: 13px; color: #78716c; margin-top: 24px; border-top: 1px solid #eee; padding-top: 16px;\">"
                . "Pozdrawiam serdecznie,<br>"
                . "<strong>Maciej Kosteczka • hellokostek</strong><br>"
                . "<a href=\"https://hellokostek.pl\" style=\"color: #E0115F; text-decoration: none;\">hellokostek.pl</a> | <a href=\"mailto:kontakt@hellokostek.pl\" style=\"color: #78716c; text-decoration: none;\">kontakt@hellokostek.pl</a>"
                . "</p>"
                . "</div>",
        );
    }
}
