<?php

namespace App\Domain\Commerce\Payments;

use Stripe\StripeClient;

class StripeApiClient
{
    private ?StripeClient $client = null;

    public function isConfigured(): bool
    {
        $config = $this->config();

        return (bool) ($config['enabled'] ?? false)
            && !empty($config['key'])
            && !empty($config['secret'])
            && !empty($config['webhook_secret']);
    }

    /**
     * @return array<int, string>
     */
    public function missingConfigurationFields(): array
    {
        $fieldLabels = self::configurationFieldLabels();
        $config = $this->config();

        return collect($fieldLabels)
            ->keys()
            ->filter(fn (string $field): bool => blank($config[$field] ?? null))
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function configurationFieldLabels(): array
    {
        return [
            'key' => 'Klucz publiczny (Publishable Key)',
            'secret' => 'Klucz prywatny (Secret Key)',
            'webhook_secret' => 'Sekret webhooka (Webhook Secret)',
        ];
    }

    public function getClient(): StripeClient
    {
        if ($this->client === null) {
            $this->client = new StripeClient($this->config()['secret'] ?? '');
        }

        return $this->client;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return (array) config('services.stripe', []);
    }
}
