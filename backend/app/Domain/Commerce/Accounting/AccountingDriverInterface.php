<?php

namespace App\Domain\Commerce\Accounting;

use App\Models\Order;

interface AccountingDriverInterface
{
    /**
     * Send order data to the external accounting system.
     *
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    public function sendOrder(Order $order): void;
}
