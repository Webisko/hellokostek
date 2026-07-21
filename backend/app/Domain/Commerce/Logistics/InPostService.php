<?php

namespace App\Domain\Commerce\Logistics;

use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use App\Support\StoreSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InPostService
{
    private const INTEGRATION = 'inpost';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService,
        private readonly StoreSettings $storeSettings
    ) {
    }

    /**
     * Create a shipment in InPost ShipX and download the label.
     */
    public function generateLabel(Order $order, string $packageSize): array
    {
        $config = config('services.inpost');
        $orgId = $config['organization_id'] ?? '';
        $token = $config['token'] ?? '';
        $sandbox = (bool) ($config['sandbox'] ?? true);

        if (empty($orgId) || empty($token)) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'shipment_creation_skipped',
                status: 'warning',
                order: $order,
                errorMessage: 'Brak konfiguracji Organization ID lub Token dla InPost.'
            );
            throw new \Exception('InPost integration is not fully configured.');
        }

        $baseUrl = $sandbox
            ? 'https://sandbox-api-shipx-pl.easypack24.net/v1'
            : 'https://api-shipx-pl.easypack24.net/v1';

        $isLocker = $this->isLockerShipment($order);

        // Resolve Receiver Details
        $shippingAddress = $order->shipping_address ?? $order->billing_address ?? [];
        $receiverPhone = $this->formatPhone($order->customer_phone);
        
        $receiverPayload = [
            'first_name' => $order->customer_first_name,
            'last_name' => $order->customer_last_name,
            'email' => $order->customer_email,
            'phone' => $receiverPhone,
        ];
        if (!empty($order->billing_company_name)) {
            $receiverPayload['company_name'] = $order->billing_company_name;
        }

        if (!$isLocker) {
            $receiverAddress = $this->parseAddressFields($shippingAddress);
            $receiverPayload['address'] = [
                'street' => $receiverAddress['street'],
                'building_number' => $receiverAddress['building_number'],
                'flat_number' => $receiverAddress['flat_number'] ?: null,
                'city' => $shippingAddress['city'] ?? '',
                'post_code' => $shippingAddress['postal_code'] ?? $shippingAddress['postcode'] ?? '',
                'country_code' => 'PL',
            ];
        }

        // Resolve Sender Details
        $senderPayload = $this->getSenderPayload();

        // Map parcel size: A -> small, B -> medium, C -> large
        $sizeMap = [
            'A' => 'small',
            'B' => 'medium',
            'C' => 'large',
        ];
        $template = $sizeMap[strtoupper($packageSize)] ?? 'medium';

        $payload = [
            'receiver' => $receiverPayload,
            'sender' => $senderPayload,
            'parcels' => [
                [
                    'template' => $template,
                ]
            ],
            'service' => $isLocker ? 'inpost_locker_standard' : 'inpost_courier_standard',
        ];

        // Locker specific target point
        if ($isLocker) {
            $lockerId = data_get($order->metadata, 'delivery_point.id');
            if (empty($lockerId)) {
                throw new \Exception('Brak identyfikatora punktu odbioru (Paczkomatu) dla tego zamówienia.');
            }
            $payload['custom_attributes'] = [
                'target_point' => trim($lockerId),
            ];
        }

        // Create Shipment
        $shipmentUrl = "{$baseUrl}/organizations/{$orgId}/shipments";
        
        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->post($shipmentUrl, $payload);

            $responseBody = $response->json();

            if (!$response->successful()) {
                $errorMsg = data_get($responseBody, 'message') ?: $response->body();
                $this->integrationLogService->record(
                    integration: self::INTEGRATION,
                    event: 'shipment_creation_failed',
                    status: 'error',
                    order: $order,
                    direction: 'outgoing',
                    requestPayload: $payload,
                    responsePayload: $responseBody,
                    errorMessage: 'Błąd InPost: ' . $errorMsg
                );
                throw new \Exception('Błąd InPost ShipX API: ' . $errorMsg);
            }

            $shipmentId = data_get($responseBody, 'id');
            $trackingNumber = data_get($responseBody, 'tracking_number');

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'shipment_created',
                status: 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: (string) $shipmentId,
                requestPayload: $payload,
                responsePayload: $responseBody
            );

            // Wait brief moment and fetch PDF label
            $labelUrl = "{$baseUrl}/shipments/{$shipmentId}/label?format=pdf";
            $labelResponse = Http::timeout(15)
                ->withToken($token)
                ->get($labelUrl);

            if ($labelResponse->successful()) {
                $pdfPath = "inpost/labels/{$trackingNumber}.pdf";
                Storage::put($pdfPath, $labelResponse->body());

                return [
                    'success' => true,
                    'shipment_id' => $shipmentId,
                    'tracking_number' => $trackingNumber,
                    'label_path' => $pdfPath,
                ];
            } else {
                Log::warning("Could not download label for shipment {$shipmentId} immediately.");
                return [
                    'success' => true,
                    'shipment_id' => $shipmentId,
                    'tracking_number' => $trackingNumber,
                    'label_path' => null, // Can be downloaded manually or retried
                ];
            }
        } catch (\Exception $e) {
            Log::error('InPost label generation exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if the order is going to an InPost Locker (Paczkomat)
     */
    private function isLockerShipment(Order $order): bool
    {
        $code = strtolower($order->shipping_method_code ?? '');
        $name = strtolower($order->shipping_method_name ?? '');

        return str_contains($code, 'locker') || str_contains($code, 'paczkomat') ||
               str_contains($name, 'paczkomat') || str_contains($name, 'inpost paczkomaty');
    }

    /**
     * Parse single street line to separate street, building, and flat number.
     * E.g. "ul. Marszałkowska 102/4" -> Street: "ul. Marszałkowska", Building: "102", Flat: "4"
     */
    private function parseAddressFields(array $address): array
    {
        $line1 = trim($address['line_1'] ?? $address['line1'] ?? '');
        
        $street = $line1;
        $building = '1';
        $flat = '';

        // Match Polish format: "Street Name 12a/4" or "Street Name 12 lok. 4" or "Street Name 12"
        if (preg_match('/^(.*?)\s*(\d+[a-zA-Z]?)(?:\s*(?:\/|lok\.|lokal)\s*(\d+))?$/ui', $line1, $matches)) {
            $street = trim($matches[1]);
            $building = trim($matches[2]);
            $flat = trim($matches[3] ?? '');
        }

        return [
            'street' => $street ?: 'Przykładowa',
            'building_number' => $building ?: '1',
            'flat_number' => $flat,
        ];
    }

    /**
     * Get sender payload from config or fall back to store settings.
     */
    private function getSenderPayload(): array
    {
        $config = config('services.inpost');
        
        $email = $config['sender_email'] ?: $this->storeSettings->supportEmail();
        $phone = $this->formatPhone($config['sender_phone'] ?: data_get($this->storeSettings->model()->metadata, 'phone'));
        $name = $config['sender_name'] ?: $this->storeSettings->storeName();
        $company = $config['sender_company'] ?: $this->storeSettings->storeName();

        $metadata = $this->storeSettings->model()->metadata ?? [];
        
        $streetLine = $config['sender_street'] ?: ($metadata['address_street'] ?? 'Przykładowa 1');
        $streetParsed = $this->parseAddressFields(['line_1' => $streetLine]);

        $city = $config['sender_city'] ?: ($metadata['address_city'] ?? 'Warszawa');
        $postcode = $config['sender_postcode'] ?: ($metadata['address_postal_code'] ?? '00-001');

        return [
            'company_name' => $company,
            'first_name' => 'Sklep',
            'last_name' => 'Wysyłki',
            'email' => $email,
            'phone' => $phone,
            'address' => [
                'street' => $streetParsed['street'],
                'building_number' => $streetParsed['building_number'],
                'flat_number' => $streetParsed['flat_number'] ?: null,
                'city' => $city,
                'post_code' => $postcode,
                'country_code' => 'PL',
            ]
        ];
    }

    private function formatPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        
        if (strlen($digits) === 9) {
            return $digits;
        }
        
        if (strlen($digits) === 11 && str_starts_with($digits, '48')) {
            return substr($digits, 2);
        }

        return substr($digits, -9) ?: '999999999';
    }
}
