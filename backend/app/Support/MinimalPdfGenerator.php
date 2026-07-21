<?php

namespace App\Support;

use App\Models\Order;

class MinimalPdfGenerator
{
    public static function generateInvoice(Order $order): string
    {
        $isInvoice = $order->payment_status === 'paid' || ($order->isCod() && in_array($order->status, ['shipped', 'completed']));
        $title = $isInvoice ? 'FAKTURA VAT' : 'FAKTURA PROFORMA';
        $number = $order->number;
        $date = $order->placed_at ? $order->placed_at->format('Y-m-d') : now()->format('Y-m-d');

        $storeSettings = app(StoreSettings::class);
        $sellerName = $storeSettings->storeName();
        $sellerEmail = $storeSettings->supportEmail() ?? '';
        
        $metadata = $storeSettings->model()->metadata ?? [];
        $sellerAddress = $metadata['address_street'] ?? 'Ulica Przykładowa 12';
        $sellerCity = ($metadata['address_postal_code'] ?? '00-001') . ' ' . ($metadata['address_city'] ?? 'Warszawa');

        $buyerName = $order->billing_company_name ?? ($order->customer_first_name . ' ' . $order->customer_last_name);
        $buyerNip = $order->billing_nip ? 'NIP: ' . $order->billing_nip : '';
        $buyerEmail = $order->customer_email;

        // Build PDF instructions
        $stream = "";
        
        // Helper to draw text
        $drawText = function(string $text, float $x, float $y, int $fontSize = 10, bool $bold = false) use (&$stream) {
            $font = $bold ? '/F2' : '/F1';
            // Simple sanitization for PDF strings
            $text = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $text);
            
            // Convert Polish characters to standard ASCII for Helvetica compatibility
            $polish = ['ą','ć','ę','ł','ń','ó','ś','ź','ż','Ą','Ć','Ę','Ł','Ń','Ó','Ś','Ź','Ż'];
            $ascii  = ['a','c','e','l','n','o','s','z','z','A','C','E','L','N','O','S','Z','Z'];
            $text = str_replace($polish, $ascii, $text);

            $stream .= "BT {$font} {$fontSize} Tf {$x} {$y} Td ({$text}) Tj ET\n";
        };

        $drawLine = function(float $x1, float $y1, float $x2, float $y2, float $width = 1.0) use (&$stream) {
            $stream .= "{$width} w {$x1} {$y1} m {$x2} {$y2} l S\n";
        };

        // Header section
        $drawText($title, 50, 750, 22, true);
        $drawText("Numer: " . $number, 50, 725, 12, true);
        $drawText("Data wystawienia: " . $date, 400, 725, 10);
        $drawLine(50, 710, 550, 710, 1.5);

        // Seller and Buyer info
        $bdo = $storeSettings->bdoNumber();
        $drawText("Sprzedawca:", 50, 680, 12, true);
        $drawText($sellerName, 50, 660, 10);
        $drawText($sellerAddress, 50, 645, 10);
        $drawText($sellerCity, 50, 630, 10);
        $drawText("Email: " . $sellerEmail, 50, 615, 10);
        if ($bdo) {
            $drawText("BDO: " . $bdo, 50, 600, 10);
        }

        $drawText("Nabywca:", 350, 680, 12, true);
        $drawText($buyerName, 350, 660, 10);
        if ($buyerNip) {
            $drawText($buyerNip, 350, 645, 10);
        }
        $drawText("Email: " . $buyerEmail, 350, 630, 10);

        $drawLine(50, 580, 550, 580, 1.0);

        // Table header
        $drawText("Nazwa produktu", 55, 570, 10, true);
        $drawText("Ilosc", 280, 570, 10, true);
        $drawText("Cena brutto", 330, 570, 10, true);
        $drawText("VAT", 410, 570, 10, true);
        $drawText("Suma brutto", 480, 570, 10, true);
        $drawLine(50, 560, 550, 560, 1.0);

        // Table items
        $y = 540;
        foreach ($order->items as $item) {
            if ($y < 100) {
                break;
            }
            
            $product = $item->product;
            $variant = $item->variant;
            
            $metadata = $item->metadata ?? [];
            if (isset($metadata['vat_rate'])) {
                $vatRate = (int) $metadata['vat_rate'];
            } else {
                $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);
            }

            $drawText(substr($item->name, 0, 32), 55, $y, 9);
            $drawText((string)$item->quantity, 280, $y, 9);
            $drawText(number_format($item->unit_price_amount / 100, 2, '.', '') . ' ' . $order->currency, 330, $y, 9);
            $vatText = $vatRate === 99 ? 'zw.' : $vatRate . '%';
            $drawText($vatText, 410, $y, 9);
            $drawText(number_format($item->total_amount / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 9);
            $y -= 20;
        }

        $drawLine(50, $y + 10, 550, $y + 10, 1.0);

        // Totals summary
        $y -= 10;
        $drawText("Suma netto:", 350, $y, 10, true);
        $netTotal = $order->total_amount - $order->tax_amount;
        $drawText(number_format($netTotal / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 10);

        $y -= 15;
        $drawText("Kwota VAT:", 350, $y, 10, true);
        $drawText(number_format($order->tax_amount / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 10);

        if ($order->discount_amount > 0) {
            $y -= 15;
            $drawText("Rabat:", 350, $y, 10, true);
            $drawText("-" . number_format($order->discount_amount / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 10);
        }

        $y -= 15;
        $drawText("Wysylka:", 350, $y, 10, true);
        $drawText(number_format($order->shipping_amount / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 10);

        $y -= 20;
        $drawLine(350, $y + 10, 550, $y + 10, 1.5);
        $drawText("RAZEM DO ZAPLATY:", 300, $y, 11, true);
        $drawText(number_format($order->total_amount / 100, 2, '.', '') . ' ' . $order->currency, 480, $y, 11, true);

        // Status info
        $y -= 40;
        if ($order->payment_status === 'paid') {
            $statusText = "Zaplacono przelewem elektronicznym.";
        } elseif ($order->isCod()) {
            $statusText = "Platnosc za pobraniem przy odbiorze.";
        } else {
            $statusText = "Oczekuje na platnosc.";
        }
        $drawText($statusText, 50, $y, 10, true);

        // MPP Split Payment check for B2B orders with total >= 15 000 PLN
        $isPln = strtoupper($order->currency) === 'PLN';
        $isB2b = !empty($order->billing_nip);
        $isMppEligible = $isPln && $isB2b && $order->total_amount >= 1500000;

        if ($isMppEligible) {
            $y -= 15;
            $drawText("Mechanizm podzielonej platnosci", 50, $y, 10, true);
        }

        // Compile PDF objects structure
        $objects = [];
        
        $catalogId = 1;
        $pagesId = 2;
        $pageId = 3;
        $contentId = 4;
        $font1Id = 5;
        $font2Id = 6;

        $objects[$catalogId - 1] = "<< /Type /Catalog /Pages {$pagesId} 0 R >>";
        $objects[$pagesId - 1] = "<< /Type /Pages /Kids [{$pageId} 0 R] /Count 1 >>";
        $objects[$pageId - 1] = "<< /Type /Page /Parent {$pagesId} 0 R /MediaBox [0 0 595 842] /Contents {$contentId} 0 R /Resources << /Font << /F1 {$font1Id} 0 R /F2 {$font2Id} 0 R >> >> >>";

        $contentLength = strlen($stream);
        $objects[$contentId - 1] = "<< /Length {$contentLength} >>\nstream\n" . $stream . "endstream";
        $objects[$font1Id - 1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$font2Id - 1] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        // Build output PDF
        $output = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $obj) {
            $id = $index + 1;
            $offsets[$id] = strlen($output);
            $output .= "{$id} 0 obj\n{$obj}\nendobj\n";
        }

        $xrefPos = strlen($output);
        $output .= "xref\n";
        $output .= "0 " . (count($objects) + 1) . "\n";
        $output .= "0000000000 65535 f \n";
        foreach ($objects as $index => $obj) {
            $id = $index + 1;
            $output .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $output .= "trailer\n";
        $output .= "<< /Size " . (count($objects) + 1) . " /Root {$catalogId} 0 R >>\n";
        $output .= "startxref\n";
        $output .= "{$xrefPos}\n";
        $output .= "%%EOF\n";

        return $output;
    }
}
