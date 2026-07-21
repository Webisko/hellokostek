<?php

namespace App\Support\Logistics;

use App\Models\Order;

interface LogisticsProviderInterface
{
    /**
     * Rejestruje przesylke w systemie kurierskim.
     *
     * @param Order $order
     * @return array{success: bool, tracking_number: string|null, label_url: string|null, error: string|null}
     */
    public function createShipment(Order $order): array;

    /**
     * Pobiera dostepne punkty odbioru (np. paczkomaty) w poblizu kodu pocztowego.
     *
     * @param string $postalCode
     * @return array<int, array{id: string, name: string, description: string, address: string}>
     */
    public function getPickupPoints(string $postalCode): array;
}
