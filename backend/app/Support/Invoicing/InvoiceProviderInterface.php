<?php

namespace App\Support\Invoicing;

use App\Models\Order;

interface InvoiceProviderInterface
{
    /**
     * Wystawia fakture na podstawie zamowienia.
     *
     * @param Order $order
     * @return array{success: bool, invoice_id: string|null, url: string|null, error: string|null}
     */
    public function createInvoiceFromOrder(Order $order): array;
}
