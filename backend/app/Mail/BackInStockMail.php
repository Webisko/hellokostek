<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackInStockMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly ?ProductVariant $variant = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = 'Produkt, na który czekasz, jest już dostępny!';
        if ($this->variant) {
            $variantName = $this->variant->optionValues->pluck('value')->join(', ');
            $subject = "Wariant [{$variantName}] produktu, na który czekasz, jest już dostępny!";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.products.back-in-stock',
        );
    }
}
