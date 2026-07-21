<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\SendOrderToAccountingJob;

class SendOrderToAccounting
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        SendOrderToAccountingJob::dispatch($event->order);
    }
}
