<?php

namespace App\Support;

use App\Domain\Commerce\Payments\Przelewy24ApiClient;
use App\Domain\Commerce\Payments\StripeApiClient;
use App\Models\IntegrationLog;
use Illuminate\Support\Arr;

class IntegrationReadiness
{
    /**
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $statuses = null;

    public function __construct(
        private readonly StoreSettings $storeSettings,
        private readonly Przelewy24ApiClient $przelewy24ApiClient,
        private readonly StripeApiClient $stripeApiClient,
    ) {
    }

    /**
     * @param  array<string, mixed>  $integrations
     * @return array<string, mixed>
     */
    public function mergeInto(array $integrations): array
    {
        foreach ($this->statuses() as $integration => $status) {
            $integrations[$integration] = array_merge(
                (array) Arr::get($integrations, $integration, []),
                [
                    'enabled' => $status['enabled'],
                    'readiness' => $status,
                ],
            );
        }

        return $integrations;
    }

    public function issueCount(): int
    {
        return collect($this->statuses())
            ->filter(fn (array $status): bool => $status['status'] === 'configuration_required')
            ->count();
    }

    public function summaryContext(): string
    {
        $statuses = collect($this->statuses());

        $missing = $statuses
            ->filter(fn (array $status): bool => $status['status'] === 'configuration_required')
            ->map(fn (array $status): string => sprintf(
                '%s (%s)',
                $status['label_name'],
                implode(', ', $status['missing_labels']),
            ))
            ->values()
            ->all();

        if ($missing !== []) {
            return 'Do uzupelnienia: ' . implode('; ', $missing);
        }

        $disabled = $statuses
            ->filter(fn (array $status): bool => $status['status'] === 'disabled')
            ->map(fn (array $status): string => $status['label_name'])
            ->values()
            ->all();

        if ($disabled !== []) {
            return 'Wylaczone: ' . implode(', ', $disabled);
        }

        return 'Przelewy24 i Stripe maja komplet konfiguracji.';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function statuses(): array
    {
        if (is_array($this->statuses)) {
            return $this->statuses;
        }

        $this->statuses = [
            'stripe' => $this->makeStatus(
                integration: 'stripe',
                enabled: $this->integrationEnabled('stripe'),
                configured: $this->stripeApiClient->isConfigured(),
                missingFields: $this->stripeApiClient->missingConfigurationFields(),
                fieldLabels: StripeApiClient::configurationFieldLabels(),
            ),
            'przelewy24' => $this->makeStatus(
                integration: 'przelewy24',
                enabled: $this->integrationEnabled('przelewy24'),
                configured: $this->przelewy24ApiClient->isConfigured(includeCallbackToken: true),
                missingFields: $this->przelewy24ApiClient->missingConfigurationFields(includeCallbackToken: true),
                fieldLabels: Przelewy24ApiClient::configurationFieldLabels(includeCallbackToken: true),
            ),
        ];

        return $this->statuses;
    }

    private function integrationEnabled(string $integration): bool
    {
        $integrations = $this->storeSettings->integrations();
        $storeSettingEnabled = Arr::has($integrations, $integration . '.enabled')
            ? (bool) Arr::get($integrations, $integration . '.enabled')
            : true;
        $environmentEnabled = (bool) config(
            'services.' . $integration . '.enabled',
            config('shop.integrations.' . $integration . '.enabled', false),
        );

        return $environmentEnabled && $storeSettingEnabled;
    }

    /**
     * @param  array<int, string>  $missingFields
     * @param  array<string, string>  $fieldLabels
     * @return array<string, mixed>
     */
    private function makeStatus(
        string $integration,
        bool $enabled,
        bool $configured,
        array $missingFields,
        array $fieldLabels,
    ): array {
        $status = ! $enabled
            ? 'disabled'
            : ($configured ? 'ready' : 'configuration_required');

        return [
            'integration' => $integration,
            'label_name' => IntegrationLog::integrationLabel($integration),
            'enabled' => $enabled,
            'configured' => $configured,
            'status' => $status,
            'label' => match ($status) {
                'ready' => 'Gotowa',
                'disabled' => 'Wylaczona',
                default => 'Wymaga konfiguracji',
            },
            'missing_fields' => $missingFields,
            'missing_labels' => collect($missingFields)
                ->map(fn (string $field): string => $fieldLabels[$field] ?? $field)
                ->values()
                ->all(),
        ];
    }
}