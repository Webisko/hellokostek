<?php

namespace App\Support\Logistics;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InPostLogisticsProvider implements LogisticsProviderInterface
{
    public function createShipment(Order $order): array
    {
        $organizationId = config('services.inpost.organization_id');
        $apiToken = config('services.inpost.api_token');
        $baseUrl = config('services.inpost.sandbox', true) 
            ? 'https://sandbox-api-shipx-pl.easypack24.net' 
            : 'https://api-shipx-pl.easypack24.net';

        if (blank($organizationId) || blank($apiToken)) {
            return [
                'success' => false,
                'tracking_number' => null,
                'label_url' => null,
                'error' => 'Brak konfiguracji API InPost (organization_id lub api_token w config/services.php).',
            ];
        }

        $pickupPointId = data_get($order->metadata, 'shipping.pickup_point_id'); // Id paczkomatu ze slownika

        $payload = [
            'receiver' => [
                'first_name' => $order->customer_first_name,
                'last_name' => $order->customer_last_name,
                'email' => $order->customer_email,
                'phone' => data_get($order->metadata, 'shipping.phone') ?: '+48123456789',
            ],
            'parcels' => [
                [
                    'template' => 'small', // small, medium, large
                    'weight' => [
                        'amount' => $order->weight ?: 1.0,
                        'unit' => 'kg'
                    ]
                ]
            ],
            'service' => $pickupPointId ? 'inpost_locker_standard' : 'inpost_courier_standard',
        ];

        if ($pickupPointId) {
            $payload['custom_attributes'] = [
                'target_point' => $pickupPointId
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}"
            ])->post("{$baseUrl}/v1/organizations/{$organizationId}/shipments", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'tracking_number' => (string) ($responseData['tracking_number'] ?? ''),
                    'label_url' => "{$baseUrl}/v1/shipments/" . ($responseData['id'] ?? '') . "/label",
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'tracking_number' => null,
                'label_url' => null,
                'error' => 'API InPost zwrocilo status: ' . $response->status() . ' - ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Blad integracji z InPost: ' . $e->getMessage());
            return [
                'success' => false,
                'tracking_number' => null,
                'label_url' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getPickupPoints(string $postalCode): array
    {
        $baseUrl = config('services.inpost.sandbox', true) 
            ? 'https://sandbox-api-shipx-pl.easypack24.net' 
            : 'https://api-shipx-pl.easypack24.net';

        try {
            $response = Http::get("{$baseUrl}/v1/points", [
                'relative_post_code' => $postalCode,
                'type' => 'parcel_locker',
                'limit' => 10,
            ]);

            if ($response->successful()) {
                $items = $response->json('items') ?? [];
                return collect($items)->map(fn ($item) => [
                    'id' => (string) ($item['name'] ?? ''),
                    'name' => (string) ($item['name'] ?? ''),
                    'description' => (string) ($item['location_description'] ?? ''),
                    'address' => trim(($item['address']['street'] ?? '') . ' ' . ($item['address']['building_number'] ?? '') . ', ' . ($item['address']['city'] ?? '')),
                ])->all();
            }

            return [];

        } catch (\Exception $e) {
            Log::error('Blad pobierania punktow InPost: ' . $e->getMessage());
            return [];
        }
    }
}
