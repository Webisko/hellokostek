<?php

namespace App\Http\Controllers\Api;

use App\Domain\Commerce\Enums\CustomerSegment;
use App\Domain\Commerce\Enums\ProductType;
use App\Http\Controllers\Controller;

use App\Support\IntegrationReadiness;
use App\Support\StoreSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthController extends Controller
{
    public function __construct(
        private readonly StoreSettings $storeSettings,
        private readonly IntegrationReadiness $integrationReadiness,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        // Public response — minimal info only
        $response = [
            'status' => 'ok',
            'app'    => config('app.name'),
        ];

        // Extended info available only for authenticated admins
        if ($request->user()?->is_admin === true) {
            $integrations = $this->integrationReadiness->mergeInto($this->storeSettings->integrations());

            $response['environment'] = config('app.env');
            $response['store'] = [
                'name'                  => $this->storeSettings->storeName(),
                'currency'              => $this->storeSettings->currency(),
                'free_shipping_threshold' => $this->storeSettings->freeShippingThreshold(),
            ];
            $response['product_types'] = array_map(
                static fn (ProductType $type) => $type->value,
                ProductType::cases(),
            );
            $response['customer_segments'] = array_map(
                static fn (CustomerSegment $segment) => $segment->value,
                CustomerSegment::cases(),
            );
            $response['integrations'] = $integrations;
        }

        return response()->json($response);
    }
}