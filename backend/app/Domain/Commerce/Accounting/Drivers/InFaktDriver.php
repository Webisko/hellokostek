<?php

namespace App\Domain\Commerce\Accounting\Drivers;

use App\Domain\Commerce\Accounting\AccountingDriverInterface;
use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class InFaktDriver implements AccountingDriverInterface
{
    private const INTEGRATION = 'infakt';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService
    ) {
    }

    public function sendOrder(Order $order): void
    {
        $config = config('accounting.drivers.infakt');

        if (empty($config['enabled'])) {
            return;
        }

        $apiKey = $config['api_key'] ?? '';

        if (empty($apiKey)) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji klucza API dla inFakt.pl.'
            );
            return;
        }

        $url = "https://api.infakt.pl/v3/invoices.json";

        $services = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);

            $taxSymbol = $vatRate === 99 ? 'zw' : (string) $vatRate;

            $services[] = [
                'name' => $item->name,
                'tax_symbol' => $taxSymbol,
                'gross_price' => $item->unit_price_amount, // in grosze/cents for inFakt API
                'quantity' => $item->quantity,
                'unit' => 'szt.',
            ];
        }

        if ($order->shipping_amount > 0) {
            $services[] = [
                'name' => 'Wysyłka - ' . ($order->shipping_method_name ?? 'Standard'),
                'tax_symbol' => '23',
                'gross_price' => $order->shipping_amount,
                'quantity' => 1,
                'unit' => 'usł.',
            ];
        }

        $billingAddress = $order->billing_address ?? [];
        $buyerName = $order->billing_company_name ?? ($order->customer_first_name . ' ' . $order->customer_last_name);

        $payload = [
            'invoice' => [
                'payment_method' => 'transfer',
                'paid_date' => now()->format('Y-m-d'),
                'client_company_name' => $buyerName,
                'client_nip' => $order->billing_nip,
                'client_email' => $order->customer_email,
                'client_street' => $billingAddress['line_1'] ?? $billingAddress['line1'] ?? '',
                'client_post_code' => $billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '',
                'client_city' => $billingAddress['city'] ?? '',
                'client_country' => 'PL',
                'services' => $services,
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-inFakt-ApiKey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $payload);

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
                throw new \Exception('Nie udało się zapisać faktury w inFakt: ' . $response->body());
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
