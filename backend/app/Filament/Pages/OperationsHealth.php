<?php

namespace App\Filament\Pages;

use App\Models\FailedJob;
use App\Models\IntegrationLog;
use App\Models\TransactionalEmailLog;
use App\Support\IntegrationReadiness;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class OperationsHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static ?string $navigationLabel = 'Status systemu';

    protected static ?string $title = 'Status systemu';

    protected static ?string $slug = 'status-systemu';

    protected static \UnitEnum|string|null $navigationGroup = 'Analityka & system';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.operations-health';

    /**
     * @return array<int, array{label: string, value: int, description: string, context: string, url: string, tone: string}>
     */
    public function stats(): array
    {
        $integrationReadiness = app(IntegrationReadiness::class);
        $failedJobsCount = FailedJob::query()->count();
        $failedEmailsCount = TransactionalEmailLog::query()->where('status', 'failed')->count();
        $integrationReadinessIssuesCount = $integrationReadiness->issueCount();
        $integrationAlertsQuery = IntegrationLog::query()
            ->where('status', '!=', 'success')
            ->where(function ($query): void {
                $query
                    ->whereNotIn('integration', ['przelewy24', 'stripe'])
                    ->orWhere(function ($query): void {
                        $query
                            ->where('integration', 'przelewy24')
                            ->where(function ($query): void {
                                $query
                                    ->where('direction', '!=', IntegrationLog::DIRECTION_OUTGOING)
                                    ->orWhere('event', 'not like', IntegrationLog::paymentSessionIssueEventPattern());
                            })
                            ->where(function ($query): void {
                                $query
                                    ->where('direction', '!=', IntegrationLog::DIRECTION_INCOMING)
                                    ->orWhereNotIn('event', IntegrationLog::paymentCallbackAlertEvents());
                            });
                    });
            });
        $integrationAlertsCount = (clone $integrationAlertsQuery)->count();
        $stripeIssuesQuery = IntegrationLog::query()
            ->where('integration', 'stripe')
            ->where('status', '!=', IntegrationLog::STATUS_SUCCESS);
        $stripeIssuesCount = (clone $stripeIssuesQuery)->count();
        $paymentSessionIssuesQuery = IntegrationLog::query()
            ->where('integration', 'przelewy24')
            ->where('direction', IntegrationLog::DIRECTION_OUTGOING)
            ->where('status', '!=', IntegrationLog::STATUS_SUCCESS)
            ->where('event', 'like', 'payment_session_%');
        $paymentSessionIssuesCount = (clone $paymentSessionIssuesQuery)->count();
        $paymentCallbackAlertsQuery = IntegrationLog::query()
            ->where('integration', 'przelewy24')
            ->where('direction', IntegrationLog::DIRECTION_INCOMING)
            ->whereIn('event', IntegrationLog::paymentCallbackAlertEvents());
        $paymentCallbackAlertsCount = (clone $paymentCallbackAlertsQuery)->count();


        $lastFailedJobAt = FailedJob::query()->max('failed_at');
        $lastFailedEmailAt = TransactionalEmailLog::query()->where('status', 'failed')->max('created_at');
        $lastIntegrationAlert = (clone $integrationAlertsQuery)
            ->latest('occurred_at')
            ->first();
        $lastStripeIssue = (clone $stripeIssuesQuery)
            ->latest('occurred_at')
            ->first();
        $lastPaymentSessionIssue = (clone $paymentSessionIssuesQuery)
            ->latest('occurred_at')
            ->first();
        $lastPaymentCallbackAlert = (clone $paymentCallbackAlertsQuery)
            ->latest('occurred_at')
            ->first();


        return [
            [
                'label' => 'Nieudane zadania',
                'value' => $failedJobsCount,
                'description' => 'Zadania w kolejce wymagające ponowienia lub analizy.',
                'context' => $this->dateContext('Ostatni błąd', $lastFailedJobAt, 'Brak zarejestrowanych nieudanych zadań.'),
                'url' => '/admin/failed-jobs',
                'tone' => $failedJobsCount > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Błędy wiadomości transakcyjnych',
                'value' => $failedEmailsCount,
                'description' => 'Nieudane wysyłki widoczne w dzienniku wiadomości e-mail.',
                'context' => $this->dateContext('Ostatni błąd', $lastFailedEmailAt, 'Brak nieudanych wysyłek.'),
                'url' => $this->transactionalEmailLogsUrl(status: 'failed'),
                'tone' => $failedEmailsCount > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Alerty integracyjne',
                'value' => $integrationAlertsCount,
                'description' => 'Wpisy w dzienniku integracji ze statusem innym niż sukces.',
                'context' => $this->integrationAlertContext($lastIntegrationAlert),
                'url' => $this->integrationLogsUrl(
                    tableFilters: [
                        'health_window' => ['value' => 'alert'],
                    ],
                ),
                'tone' => $integrationAlertsCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Gotowość integracji',
                'value' => $integrationReadinessIssuesCount,
                'description' => 'Integracje wymagające uzupełnienia kluczy lub adresów środowiskowych.',
                'context' => $integrationReadiness->summaryContext(),
                'url' => '/admin/store-settings',
                'tone' => $integrationReadinessIssuesCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Problemy ze Stripe',
                'value' => $stripeIssuesCount,
                'description' => 'Błędy rejestracji sesji lub komunikacji z webhookiem Stripe.',
                'context' => $this->stripeIssueContext($lastStripeIssue),
                'url' => $this->integrationLogsUrl(
                    null,
                    [
                        'integration' => ['value' => 'stripe'],
                    ],
                ),
                'tone' => $stripeIssuesCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Problemy sesji płatności',
                'value' => $paymentSessionIssuesCount,
                'description' => 'Błędy, braki konfiguracji albo odrzucone próby utworzenia sesji Przelewy24.',
                'context' => $this->paymentSessionIssueContext($lastPaymentSessionIssue),
                'url' => $this->integrationLogsUrl(
                    tableFilters: [
                        'health_window' => ['value' => 'payment_session_issue'],
                    ],
                ),
                'tone' => $paymentSessionIssuesCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Problemy powiadomień zwrotnych płatności',
                'value' => $paymentCallbackAlertsCount,
                'description' => 'Nieautoryzowane lub odrzucone powiadomienia zwrotne Przelewy24 wymagające sprawdzenia integracji.',
                'context' => $this->paymentCallbackAlertContext($lastPaymentCallbackAlert),
                'url' => $this->integrationLogsUrl(
                    tableFilters: [
                        'health_window' => ['value' => 'payment_callback_issue'],
                    ],
                ),
                'tone' => $paymentCallbackAlertsCount > 0 ? 'danger' : 'success',
            ],

        ];
    }

    private function transactionalEmailLogsUrl(?string $status = null): string
    {
        if (blank($status)) {
            return '/admin/transactional-email-logs';
        }

        return '/admin/transactional-email-logs?' . http_build_query([
            'tableFilters' => [
                'status' => [
                    'value' => $status,
                ],
            ],
        ]);
    }



    private function dateContext(string $label, mixed $value, string $fallback): string
    {
        if (blank($value)) {
            return $fallback;
        }

        return $label . ': ' . Carbon::parse($value)->format('Y-m-d H:i');
    }

    private function integrationAlertContext(?IntegrationLog $log): string
    {
        if ($log === null) {
            return 'Brak alertów integracyjnych.';
        }

        return sprintf(
            'Ostatni alert: %s | %s | %s',
            Carbon::parse($log->occurred_at)->format('Y-m-d H:i'),
            IntegrationLog::integrationLabel($log->integration),
            IntegrationLog::eventLabel($log->event),
        );
    }

    private function paymentCallbackAlertContext(?IntegrationLog $log): string
    {
        if ($log === null) {
            return 'Brak problematycznych powiadomień zwrotnych płatności.';
        }

        return sprintf(
            'Ostatni problem: %s | %s | %s',
            Carbon::parse($log->occurred_at)->format('Y-m-d H:i'),
            IntegrationLog::integrationLabel($log->integration),
            IntegrationLog::eventLabel($log->event),
        );
    }

    private function stripeIssueContext(?IntegrationLog $log): string
    {
        if ($log === null) {
            return 'Brak problemów ze Stripe.';
        }

        return sprintf(
            'Ostatni problem: %s | %s | %s',
            Carbon::parse($log->occurred_at)->format('Y-m-d H:i'),
            IntegrationLog::integrationLabel($log->integration),
            IntegrationLog::eventLabel($log->event),
        );
    }

    private function paymentSessionIssueContext(?IntegrationLog $log): string
    {
        if ($log === null) {
            return 'Brak problemów z tworzeniem sesji płatności.';
        }

        return sprintf(
            'Ostatni problem: %s | %s | %s',
            Carbon::parse($log->occurred_at)->format('Y-m-d H:i'),
            IntegrationLog::integrationLabel($log->integration),
            IntegrationLog::eventLabel($log->event),
        );
    }

    /**
     * @param  array<string, array<string, string|array<int, string>>>  $tableFilters
     */
    private function integrationLogsUrl(?string $tableSearch = null, array $tableFilters = []): string
    {
        $query = array_filter([
            'tableSearch' => $tableSearch,
            'tableFilters' => $tableFilters,
        ]);

        if ($query === []) {
            return '/admin/integration-logs';
        }

        return '/admin/integration-logs?' . http_build_query($query);
    }
}