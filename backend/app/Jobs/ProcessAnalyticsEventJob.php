<?php
 
namespace App\Jobs;
 
use App\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\QueryException;
 
class ProcessAnalyticsEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $attributes,
        protected string $deduplicationKey,
    ) {
    }
 
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            AnalyticsEvent::query()->firstOrCreate(
                ['deduplication_key' => $this->deduplicationKey],
                $this->attributes,
            );
        } catch (QueryException) {
            // Already created by a concurrent request, which is fine
        }
    }
}
