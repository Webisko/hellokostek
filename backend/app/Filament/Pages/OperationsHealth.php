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

    protected static \UnitEnum|string|null $navigationGroup = 'System & Ustawienia';

    protected static ?int $navigationSort = 49;

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
                'description' => 'Joby wymagajace retry lub analizy.',
                'context' => $this->dateContext('Ostatni blad', $lastFailedJobAt, 'Brak zarejestrowanych nieudanych zadan.'),
                'url' => '/admin/failed-jobs',
                'tone' => $failedJobsCount > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Bledy maili transakcyjnych',
                'value' => $failedEmailsCount,
                'description' => 'Nieudane wysylki widoczne w logach maili.',
                'context' => $this->dateContext('Ostatni blad', $lastFailedEmailAt, 'Brak nieudanych wysylek.'),
                'url' => $this->transactionalEmailLogsUrl(status: 'failed'),
                'tone' => $failedEmailsCount > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Alerty integracyjne',
                'value' => $integrationAlertsCount,
                'description' => 'Logi integracji ze statusem innym niz success.',
                'context' => $this->integrationAlertContext($lastIntegrationAlert),
                'url' => $this->integrationLogsUrl(
                    tableFilters: [
                        'health_window' => ['value' => 'alert'],
                    ],
                ),
                'tone' => $integrationAlertsCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Gotowosc integracji',
                'value' => $integrationReadinessIssuesCount,
                'description' => 'Integracje wymagajace uzupelnienia sekretow lub URL-i srodowiskowych.',
                'context' => $integrationReadiness->summaryContext(),
                'url' => '/admin/store-settings',
                'tone' => $integrationReadinessIssuesCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Problemy Stripe',
                'value' => $stripeIssuesCount,
                'description' => 'Bledy rejestracji sesji lub webhooka Stripe.',
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
                'label' => 'Problemy sesji platnosci',
                'value' => $paymentSessionIssuesCount,
                'description' => 'Bledy, braki konfiguracji albo odrzucone proby utworzenia sesji Przelewy24.',
                'context' => $this->paymentSessionIssueContext($lastPaymentSessionIssue),
                'url' => $this->integrationLogsUrl(
                    tableFilters: [
                        'health_window' => ['value' => 'payment_session_issue'],
                    ],
                ),
                'tone' => $paymentSessionIssuesCount > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Problemy callbackow platnosci',
                'value' => $paymentCallbackAlertsCount,
                'description' => 'Nieautoryzowane lub odrzucone callbacki Przelewy24 wymagajace sprawdzenia integracji.',
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
            return 'Brak alertow integracyjnych.';
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
            return 'Brak problematycznych callbackow platnosci.';
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
            return 'Brak problemow ze Stripe.';
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
            return 'Brak problemow z tworzeniem sesji platnosci.';
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