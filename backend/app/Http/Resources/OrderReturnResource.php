<?php

namespace App\Http\Resources;

use App\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderReturn
 */
class OrderReturnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->number,
            'status' => $this->status,
            'reason' => $this->reason,
            'tracking_number' => $this->tracking_number,
            'refund_amount' => $this->refund_amount,
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'order_item_id' => $item->order_item_id,
                'quantity' => (int) $item->quantity,
                'product_name' => $item->orderItem?->name,
                'product_sku' => $item->orderItem?->sku,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
