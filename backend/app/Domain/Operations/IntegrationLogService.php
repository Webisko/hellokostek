<?php

namespace App\Domain\Operations;

use App\Models\IntegrationLog;
use App\Models\Order;

class IntegrationLogService
{
    public function record(
        string $integration,
        string $event,
        string $status,
        ?Order $order = null,
        string $direction = 'outgoing',
        ?string $externalReference = null,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
        ?array $metadata = null,
    ): IntegrationLog {
        return IntegrationLog::query()->create([
            'order_id' => $order?->id,
            'integration' => $integration,
            'event' => $event,
            'direction' => $direction,
            'status' => $status,
            'external_reference' => $externalReference,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}