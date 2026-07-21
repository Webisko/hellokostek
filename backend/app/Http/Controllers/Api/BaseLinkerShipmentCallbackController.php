<?php

namespace App\Http\Controllers\Api;

use App\Domain\Integrations\BaseLinker\BaseLinkerShipmentStatusSyncService;
use App\Domain\Operations\IntegrationLogService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseLinkerShipmentCallbackController extends Controller
{
    public function __construct(
        private readonly BaseLinkerShipmentStatusSyncService $shipmentStatusSyncService,
        private readonly IntegrationLogService $integrationLogService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'order_id' => ['nullable', 'integer', 'required_without:order_number'],
            'order_number' => ['nullable', 'string', 'required_without:order_id'],
        ]);

        if (! $this->requestIsAuthentic($request)) {
            $this->integrationLogService->record(
                integration: 'baselinker',
                event: 'webhook_rejected_invalid_signature',
                status: 'warning',
                direction: 'incoming',
                externalReference: (string) ($payload['order_id'] ?? $payload['order_number'] ?? 'unknown'),
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                ],
                errorMessage: 'Odrzucono callback BaseLinkera z powodu niepoprawnego sekretu webhooka.',
            );

            abort(403);
        }

        $order = $this->resolveOrder($payload);

        abort_if(! $order instanceof Order, 404, 'Nie znaleziono zamowienia dla callbacku BaseLinkera.');

        $result = $this->shipmentStatusSyncService->sync($order);

        return response()->json([
            'data' => [
                'order_number' => $order->number,
                'fulfillment_status' => $result['current_fulfillment_status'],
                'changed' => $result['changed'],
                'package_count' => $result['package_count'],
                'summary' => $result['summary'],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveOrder(array $payload): ?Order
    {
        $orderNumber = $payload['order_number'] ?? null;

        if (filled($orderNumber)) {
            return Order::query()
                ->where('number', (string) $orderNumber)
                ->first();
        }

        $baseLinkerOrderId = (int) ($payload['order_id'] ?? 0);

        return Order::query()
            ->where(function ($query) use ($baseLinkerOrderId): void {
                $query
                    ->where('metadata->integrations->baselinker->order_id', $baseLinkerOrderId)
                    ->orWhere('metadata->integrations->baselinker->order_id', (string) $baseLinkerOrderId);
            })
            ->first();
    }

    private function requestIsAuthentic(Request $request): bool
    {
        $secret = (string) config('services.baselinker.webhook_secret');
        $receivedSecret = (string) (
            $request->header('X-BaseLinker-Webhook-Secret')
            ?: $request->header('X-Webhook-Secret')
            ?: $request->input('secret', '')
        );

        return filled($secret) && hash_equals($secret, $receivedSecret);
    }
}
