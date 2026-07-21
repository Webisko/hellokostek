<?php

namespace App\Domain\Commerce\Accounting\Drivers;

use App\Domain\Commerce\Accounting\AccountingDriverInterface;
use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class FakturowniaDriver implements AccountingDriverInterface
{
    private const INTEGRATION = 'fakturownia';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService
    ) {
    }

    public function sendOrder(Order $order): void
    {
        $config = config('accounting.drivers.fakturownia');

        if (empty($config['enabled'])) {
            return;
        }

        $apiToken = $config['api_token'] ?? '';
        $domain = $config['domain'] ?? '';

        if (empty($apiToken) || empty($domain)) {
            // Log warning that configuration is missing
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji tokenu lub domeny dla Fakturownia.pl.'
            );
            return;
        }

        $url = "https://{$domain}.fakturownia.pl/invoices.json";

        // Build Fakturownia invoice payload
        $positions = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);

            $positions[] = [
                'name' => $item->name,
                'tax' => $vatRate === 99 ? 'zw' : $vatRate,
                'total_price_gross' => $item->total_amount / 100,
                'quantity' => $item->quantity,
            ];
        }

        // Add shipping if any
        if ($order->shipping_amount > 0) {
            $positions[] = [
                'name' => 'Wysyłka - ' . ($order->shipping_method_name ?? 'Standard'),
                'tax' => 23,
                'total_price_gross' => $order->shipping_amount / 100,
                'quantity' => 1,
            ];
        }

        $buyerName = $order->billing_company_name ?? ($order->customer_first_name . ' ' . $order->customer_last_name);
        $billingAddress = $order->billing_address ?? [];
        $buyerAddress = implode(', ', array_filter([
            ($billingAddress['line_1'] ?? $billingAddress['line1'] ?? ''),
            ($billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '') . ' ' . ($billingAddress['city'] ?? ''),
        ]));

        $payload = [
            'api_token' => $apiToken,
            'invoice' => [
                'kind' => 'vat',
                'sell_date' => $order->placed_at ? $order->placed_at->format('Y-m-d') : now()->format('Y-m-d'),
                'issue_date' => now()->format('Y-m-d'),
                'payment_to' => now()->format('Y-m-d'),
                'buyer_name' => $buyerName,
                'buyer_nip' => $order->billing_nip,
                'buyer_email' => $order->customer_email,
                'buyer_post_code' => $billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '',
                'buyer_city' => $billingAddress['city'] ?? '',
                'buyer_street' => $billingAddress['line_1'] ?? $billingAddress['line1'] ?? '',
                'positions' => $positions,
            ],
        ];

        try {
            $response = Http::timeout(10)->post($url, $payload);
            
            $responseBody = $response->json();
            $status = $response->successful() ? 'success' : 'error';
            $externalRef = data_get($responseBody, 'id');

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent',
                status: $status,
                order: $order,
                direction: 'outgoing',
                externalReference: (string) $externalRef,
                requestPayload: $payload,
                responsePayload: $responseBody,
                errorMessage: $response->successful() ? null : 'Błąd API: ' . $response->body()
            );

            if (!$response->successful()) {
                throw new \Exception('Nie udało się zapisać faktury w Fakturownia: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                requestPayload: $payload,
                errorMessage: $e->getMessage()
            );

            throw $e;
        }
    }
}
