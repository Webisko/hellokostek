<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncNewsletterToWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $email,
        protected string $status,
        protected ?string $firstName = null,
        protected ?string $lastName = null,
        protected array $metadata = []
    ) {
    }

    public function handle(): void
    {
        $settings = app(\App\Support\StoreSettings::class);
        $webhookUrl = data_get($settings->model()->metadata, 'newsletter_webhook_url');

        if (blank($webhookUrl)) {
            return;
        }

        try {
            $payload = [
                'event' => 'newsletter_subscriber_updated',
                'email' => $this->email,
                'status' => $this->status,
                'first_name' => $this->firstName,
                'last_name' => $this->lastName,
                'metadata' => $this->metadata,
                'timestamp' => now()->toIso8601String(),
            ];

            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if (!$response->successful()) {
                Log::warning('Synchronizacja newslettera z webhookiem zwróciła status: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('Błąd podczas synchronizacji newslettera z webhookiem: ' . $e->getMessage());
        }
    }
}
