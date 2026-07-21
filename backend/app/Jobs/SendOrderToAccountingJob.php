<?php

namespace App\Jobs;

use App\Models\Order;
use App\Domain\Commerce\Accounting\Drivers\FakturowniaDriver;
use App\Domain\Commerce\Accounting\Drivers\IFirmaDriver;
use App\Domain\Commerce\Accounting\Drivers\InFaktDriver;
use App\Domain\Commerce\Accounting\Drivers\WFirmaDriver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderToAccountingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly Order $order
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $drivers = [
            'built_in' => \App\Domain\Commerce\Accounting\Drivers\BuiltInInvoiceDriver::class,
            'fakturownia' => FakturowniaDriver::class,
            'ifirma' => IFirmaDriver::class,
            'infakt' => InFaktDriver::class,
            'wfirma' => WFirmaDriver::class,
        ];

        $metadata = $this->order->metadata ?? [];
        $completed = $metadata['accounting_completed'] ?? [];
        
        $hasFailures = false;
        $errors = [];

        foreach ($drivers as $key => $driverClass) {
            if (!config("accounting.drivers.{$key}.enabled")) {
                continue;
            }

            // Skip if already successfully completed
            if (in_array($key, $completed, true)) {
                continue;
            }

            try {
                $driver = app($driverClass);
                $driver->sendOrder($this->order);

                // Add to completed list
                $completed[] = $key;
                
                // Save progress immediately
                $metadata['accounting_completed'] = $completed;
                $this->order->forceFill(['metadata' => $metadata])->save();
            } catch (\Exception $e) {
                Log::error("Failed to send order {$this->order->number} to accounting platform '{$key}': " . $e->getMessage());
                $hasFailures = true;
                $errors[] = "{$key}: " . $e->getMessage();
            }
        }

        // If there was any failure, throw exception so the queue worker retries
        if ($hasFailures) {
            throw new \RuntimeException(
                "Accounting job finished with partial failures: " . implode(', ', $errors)
            );
        }
    }
}
