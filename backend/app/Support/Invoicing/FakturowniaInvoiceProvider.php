<?php

namespace App\Support\Invoicing;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FakturowniaInvoiceProvider implements InvoiceProviderInterface
{
    public function createInvoiceFromOrder(Order $order): array
    {
        $domain = config('services.fakturownia.domain'); // np. 'mojafirma'
        $apiToken = config('services.fakturownia.api_token');

        if (blank($domain) || blank($apiToken)) {
            return [
                'success' => false,
                'invoice_id' => null,
                'url' => null,
                'error' => 'Brak konfiguracji API Fakturownia (domain lub api_token w config/services.php).',
            ];
        }

        $positions = [];
        foreach ($order->items as $item) {
            $positions[] = [
                'name' => $item->name,
                'tax' => $item->metadata['vat_rate'] ?? 23,
                'total_price_gross' => number_format($item->total_amount / 100, 2, '.', ''),
                'quantity' => $item->quantity,
            ];
        }

        // Dodaj koszt wysylki jako osobna pozycje, jesli wystepuje
        if ($order->shipping_amount > 0) {
            $positions[] = [
                'name' => 'Koszt dostawy',
                'tax' => 23, // Domyslnie 23% VAT dla wysylki
                'total_price_gross' => number_format($order->shipping_amount / 100, 2, '.', ''),
                'quantity' => 1,
            ];
        }

        $payload = [
            'api_token' => $apiToken,
            'invoice' => [
                'kind' => 'vat',
                'number' => null, // Fakturownia nada automatycznie kolejny numer
                'sell_date' => now()->format('Y-m-d'),
                'issue_date' => now()->format('Y-m-d'),
                'payment_to' => now()->format('Y-m-d'),
                'seller_name' => config('app.name'),
                'buyer_name' => trim($order->customer_first_name . ' ' . $order->customer_last_name) . ($order->company_name ? ', ' . $order->company_name : ''),
                'buyer_email' => $order->customer_email,
                'buyer_tax_no' => $order->nip,
                'positions' => $positions,
            ]
        ];

        try {
            $response = Http::post("https://{$domain}.fakturownia.pl/invoices.json", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'invoice_id' => (string) ($responseData['id'] ?? ''),
                    'url' => (string) ($responseData['view_url'] ?? ''),
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'invoice_id' => null,
                'url' => null,
                'error' => 'API Fakturownia zwrocilo status: ' . $response->status() . ' - ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Blad integracji z Fakturownia: ' . $e->getMessage());
            return [
                'success' => false,
                'invoice_id' => null,
                'url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
