<?php

namespace App\Domain\Commerce\Accounting\Drivers;

use App\Domain\Commerce\Accounting\AccountingDriverInterface;
use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class WFirmaDriver implements AccountingDriverInterface
{
    private const INTEGRATION = 'wfirma';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService
    ) {
    }

    public function sendOrder(Order $order): void
    {
        $config = config('accounting.drivers.wfirma');

        if (empty($config['enabled'])) {
            return;
        }

        $apiKey = $config['api_key'] ?? '';
        $accessKey = $config['access_key'] ?? '';

        if (empty($apiKey) || empty($accessKey)) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji klucza lub klucza dostępu dla wFirma.pl.'
            );
            return;
        }

        $url = "https://api2.wfirma.pl/invoices/add";

        $invoiceContents = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);

            $vatSymbol = $vatRate === 99 ? 'zw' : (string) $vatRate;

            $invoiceContents[] = [
                'invoicecontent' => [
                    'name' => $item->name,
                    'unit' => 'szt.',
                    'count' => $item->quantity,
                    'price' => $item->unit_price_amount / 100, // Gross unit price
                    'vat' => $vatSymbol,
                ],
            ];
        }

        if ($order->shipping_amount > 0) {
            $invoiceContents[] = [
                'invoicecontent' => [
                    'name' => 'Wysyłka - ' . ($order->shipping_method_name ?? 'Standard'),
                    'unit' => 'usł.',
                    'count' => 1,
                    'price' => $order->shipping_amount / 100,
                    'vat' => '23',
                ],
            ];
        }

        $billingAddress = $order->billing_address ?? [];
        $buyerName = $order->billing_company_name ?? ($order->customer_first_name . ' ' . $order->customer_last_name);

        $payload = [
            'invoices' => [
                [
                    'invoice' => [
                        'paymentmethod' => 'transfer',
                        'paymentstate' => 'paid',
                        'price_type' => 'gross', // Calculate taxes from gross prices
                        'contractor' => [
                            'name' => $buyerName,
                            'nip' => $order->billing_nip,
                            'street' => $billingAddress['line_1'] ?? $billingAddress['line1'] ?? '',
                            'zip' => $billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '',
                            'city' => $billingAddress['city'] ?? '',
                            'country' => 'PL',
                            'email' => $order->customer_email,
                        ],
                        'invoicecontents' => $invoiceContents,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($accessKey, $apiKey)
                ->post($url, $payload);

            $responseBody = $response->json();
            $status = $response->successful() ? 'success' : 'error';
            
            // Extract wFirma invoice ID
            $externalRef = data_get($responseBody, 'invoices.0.invoice.id');

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
                throw new \Exception('Nie udało się zapisać faktury w wFirma: ' . $response->body());
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
