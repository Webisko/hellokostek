<?php

namespace App\Domain\Commerce\Fulfillment;

use App\Domain\Commerce\Enums\ProductType;
use App\Models\Order;
use App\Models\OrderFulfillmentAction;
use App\Models\OrderItem;

class OrderFulfillmentPlanner
{
    public function ensurePlanned(Order $order): void
    {
        $order->loadMissing(['items.product', 'fulfillmentActions']);

        if ($order->fulfillmentActions->isNotEmpty()) {
            return;
        }

        $physicalItems = [];
        $digitalAndServiceItems = [];

        foreach ($order->items as $item) {
            if ($item->product_type === ProductType::Physical) {
                $physicalItems[] = $item;
            } else {
                $digitalAndServiceItems[] = $item;
            }
        }

        // 1. Zaplanuj zbiorczą wysyłkę fizyczną, jeśli są produkty fizyczne
        if (!empty($physicalItems)) {
            $itemsList = collect($physicalItems)->map(function ($item) {
                return $item->name . ' (x' . $item->quantity . ')';
            })->implode(', ');

            OrderFulfillmentAction::query()->create([
                'order_id' => $order->id,
                'order_item_id' => null,
                'action_type' => 'physical_shipping',
                'status' => 'pending',
                'title' => 'Przygotuj wysyłkę produktów fizycznych',
                'instructions' => 'Spakuj produkty fizyczne: ' . $itemsList . '. Przygotuj nadanie według wybranej metody wysyłki: ' . ($order->shipping_method_name ?? 'brak wybranej metody') . '.',
                'due_at' => now()->addDay(),
                'metadata' => [
                    'product_type' => 'physical',
                    'items_count' => count($physicalItems),
                ],
            ]);
        }

        // 2. Zaplanuj indywidualne akcje dla produktów cyfrowych i usług
        foreach ($digitalAndServiceItems as $item) {
            $plan = $this->planForItem($order, $item);

            OrderFulfillmentAction::query()->create([
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'action_type' => $plan['action_type'],
                'status' => 'pending',
                'title' => $plan['title'],
                'instructions' => $plan['instructions'],
                'due_at' => $plan['due_at'],
                'metadata' => [
                    'item_name' => $item->name,
                    'product_type' => $item->product_type->value,
                    'slug' => $item->metadata['slug'] ?? null,
                ],
            ]);
        }
    }

    /**
     * @return array{action_type: string, title: string, instructions: string, due_at: \DateTimeInterface|string|null}
     */
    private function planForItem(Order $order, OrderItem $item): array
    {
        return match ($item->product_type) {
            ProductType::Digital => [
                'action_type' => 'digital_delivery',
                'title' => 'Wyslij dostep cyfrowy: ' . $item->name,
                'instructions' => 'Przygotuj i wyslij klientowi email z linkiem do pobrania lub dostepem do materialu cyfrowego.',
                'due_at' => now()->addHour(),
            ],
            ProductType::Service => [
                'action_type' => 'service_followup',
                'title' => 'Rozpocznij obsluge uslugi: ' . $item->name,
                'instructions' => 'Wyslij klientowi instrukcje startowe i zaplanuj reczny fulfillment uslugi w oknie 5-10 dni roboczych.',
                'due_at' => now()->addDay(),
            ],
            default => [
                'action_type' => 'physical_shipping',
                'title' => 'Przygotuj wysylke: ' . $item->name,
                'instructions' => 'Spakuj pozycje fizyczne i przygotuj nadanie wedlug wybranej metody wysylki: ' . ($order->shipping_method_name ?? 'brak'),
                'due_at' => now()->addDay(),
            ],
        };
    }

}