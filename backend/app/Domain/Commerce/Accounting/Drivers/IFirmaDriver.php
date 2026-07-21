<?php

namespace App\Domain\Commerce\Accounting\Drivers;

use App\Domain\Commerce\Accounting\AccountingDriverInterface;
use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

class IFirmaDriver implements AccountingDriverInterface
{
    private const INTEGRATION = 'ifirma';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService
    ) {
    }

    public function sendOrder(Order $order): void
    {
        $config = config('accounting.drivers.ifirma');

        if (empty($config['enabled'])) {
            return;
        }

        $apiKey = $config['api_key'] ?? '';
        $username = $config['username'] ?? '';

        if (empty($apiKey) || empty($username)) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'order_sent_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji klucza lub użytkownika dla iFirma.pl.'
            );
            return;
        }

        $url = "https://www.ifirma.pl/iapi/fakturakraj.json";

        $positions = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            $variant = $item->variant;
            $vatRate = $variant ? ($variant->vat_rate ?? 23) : ($product ? ($product->vat_rate ?? 23) : 23);

            // iFirma expects decimal VAT e.g. 0.23 for 23%, or 'zw' for exempt
            $vatValue = $vatRate === 99 ? 'zw' : ($vatRate / 100);

            $positions[] = [
                'StawkaVat' => $vatValue,
                'Ilosc' => $item->quantity,
                'CenaJednostkowa' => $item->unit_price_amount / 100,
                'NazwaPelna' => $item->name,
                'Jednostka' => 'szt.',
                'TypStawkiVat' => $vatRate === 99 ? 'ZW' : 'PRC',
            ];
        }

        if ($order->shipping_amount > 0) {
            $positions[] = [
                'StawkaVat' => 0.23,
                'Ilosc' => 1,
                'CenaJednostkowa' => $order->shipping_amount / 100,
                'NazwaPelna' => 'Wysyłka - ' . ($order->shipping_method_name ?? 'Standard'),
                'Jednostka' => 'usł.',
                'TypStawkiVat' => 'PRC',
            ];
        }

        $billingAddress = $order->billing_address ?? [];
        $buyerName = $order->billing_company_name ?? ($order->customer_first_name . ' ' . $order->customer_last_name);

        $payload = [
            'Zaplacono' => $order->total_amount / 100,
            'ZaplaconoNaDokumencie' => $order->total_amount / 100,
            'LiczOd' => 'BRT', // Calculate from gross
            'MiejsceWystawienia' => $billingAddress['city'] ?? '',
            'Nabywca' => [
                'Nazwa' => $buyerName,
                'NIP' => $order->billing_nip,
                'Ulica' => $billingAddress['line_1'] ?? $billingAddress['line1'] ?? '',
                'KodPocztowy' => $billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '',
                'Miejscowosc' => $billingAddress['city'] ?? '',
                'Kraj' => 'PL',
                'Email' => $order->customer_email,
            ],
            'Pozycje' => $positions,
        ];

        // iFirma uses custom Authentication header: IAPIS user="...", key="..."
        // Key is HMAC-SHA1 of: url + username + key_name + request_body using the api_key as key.
        // For invoices, key_name is 'faktura'.
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $hashString = $url . $username . 'faktura' . $jsonPayload;
        $binaryApiKey = pack('H*', $apiKey);
        $signature = hash_hmac('sha1', $hashString, $binaryApiKey);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Authentication' => 'IAPIS user="' . $username . '", key="' . $signature . '"',
                ])
                ->withBody($jsonPayload, 'application/json')
                ->post($url);

            $responseBody = $response->json();
            $status = $response->successful() ? 'success' : 'error';
            $externalRef = data_get($responseBody, 'response.IdIdentyfikatora');

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
                throw new \Exception('Nie udało się zapisać faktury w iFirma: ' . $response->body());
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
