<?php

namespace App\Domain\Commerce\Payments;

use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentCallbackService
{
    private const INTEGRATION = 'przelewy24';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService,
        private readonly Przelewy24ApiClient $przelewy24ApiClient,
    ) {
    }

    public function notificationIsAuthentic(array $payload): bool
    {
        return $this->przelewy24ApiClient->notificationMatchesConfiguredMerchant($payload)
            && $this->przelewy24ApiClient->notificationSignatureIsValid($payload);
    }

    /**
     * @return array{transaction: PaymentTransaction, order: Order, replayed: bool}
     */
    public function handlePrzelewy24Notification(array $payload): array
    {
        $sessionId = (string) $payload['sessionId'];
        $transaction = PaymentTransaction::query()
            ->with('order')
            ->where('provider', self::INTEGRATION)
            ->where('external_session_id', $sessionId)
            ->first();

        if ($transaction === null || $transaction->order === null) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_rejected_transaction_not_found',
                status: 'warning',
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                ],
                errorMessage: 'Nie znaleziono transakcji platnosci dla przekazanego sessionId.',
            );

            throw ValidationException::withMessages([
                'sessionId' => 'Nie znaleziono transakcji platnosci dla przekazanego sessionId.',
            ]);
        }

        $order = $transaction->order;
        $providerStatus = 'verified';

        if (in_array($transaction->status, ['confirmed', 'failed'], true)) {
            if ($transaction->status === 'confirmed') {
                $this->integrationLogService->record(
                    integration: self::INTEGRATION,
                    event: 'payment_callback_replayed',
                    status: 'success',
                    order: $order,
                    direction: 'incoming',
                    externalReference: $sessionId,
                    requestPayload: $payload,
                    responsePayload: [
                        'outcome' => 'replayed',
                        'transaction_status' => $transaction->status,
                    ],
                    errorMessage: $transaction->error_message,
                    metadata: [
                        'payment_transaction_id' => $transaction->id,
                        'provider_status' => $providerStatus,
                    ],
                );

                return [
                    'transaction' => $transaction,
                    'order' => $order,
                    'replayed' => true,
                ];
            }

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_rejected_conflicting_status',
                status: 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                    'transaction_status' => $transaction->status,
                ],
                errorMessage: 'Otrzymano callback dla transakcji juz zakonczonej niepowodzeniem.',
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            throw ValidationException::withMessages([
                'status' => 'Otrzymano callback dla transakcji juz zakonczonej niepowodzeniem.',
            ]);
        }

        $verifyPayload = [
            'merchantId' => (int) $payload['merchantId'],
            'posId' => (int) $payload['posId'],
            'sessionId' => $sessionId,
            'amount' => (int) $payload['amount'],
            'currency' => (string) $payload['currency'],
            'orderId' => (int) $payload['orderId'],
            'sign' => $this->przelewy24ApiClient->makeVerifySign(
                $sessionId,
                (int) $payload['orderId'],
                (int) $payload['amount'],
                (string) $payload['currency'],
            ),
        ];

        try {
            $verifyResponse = $this->przelewy24ApiClient->verifyTransaction($verifyPayload);
        } catch (Przelewy24ApiException $exception) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_verification_failed',
                status: 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: is_array($exception->responsePayload)
                    ? $exception->responsePayload
                    : ['outcome' => 'verification_failed'],
                errorMessage: $exception->getMessage(),
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            throw ValidationException::withMessages([
                'payment' => 'Nie udalo sie zweryfikowac potwierdzenia platnosci w Przelewy24.',
            ]);
        }

        if ((string) data_get($verifyResponse, 'data.status') !== 'success') {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_verification_failed',
                status: 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: $verifyResponse,
                errorMessage: 'Przelewy24 nie potwierdzilo transakcji w kroku verification.',
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            throw ValidationException::withMessages([
                'payment' => 'Przelewy24 nie potwierdzilo transakcji w kroku verification.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $order, $payload, $sessionId, $providerStatus, $verifyResponse): array {
            $responsePayload = $transaction->response_payload ?? [];
            $responsePayload['callback'] = [
                'order_id' => (int) $payload['orderId'],
                'method_id' => (int) $payload['methodId'],
                'statement' => (string) $payload['statement'],
                'received_at' => now()->toIso8601String(),
            ];
            $responsePayload['verification'] = $verifyResponse;

            $transaction->forceFill([
                'status' => 'confirmed',
                'response_payload' => $responsePayload,
                'error_message' => null,
                'confirmed_at' => $transaction->confirmed_at ?? now(),
                'failed_at' => null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'callback_provider_status' => $providerStatus,
                    'p24_order_id' => (int) $payload['orderId'],
                    'p24_method_id' => (int) $payload['methodId'],
                    'p24_statement' => (string) $payload['statement'],
                ]),
            ])->save();

            $order->forceFill([
                'payment_status' => 'paid',
            ])->save();

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_confirmed',
                status: 'success',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'transaction_status' => $transaction->status,
                    'order_payment_status' => $order->payment_status,
                    'verification_status' => data_get($verifyResponse, 'data.status'),
                ],
                errorMessage: null,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            return [
                'transaction' => $transaction->fresh(),
                'order' => $order->fresh(),
                'replayed' => false,
            ];
        });
    }

    /**
     * @return array{transaction: PaymentTransaction, order: Order, replayed: bool}
     */
    public function handle(array $payload): array
    {
        $sessionId = (string) $payload['session_id'];
        $orderNumber = (string) $payload['order_number'];
        $incomingStatus = (string) $payload['status'];
        $providerStatus = (string) ($payload['provider_status'] ?? $incomingStatus);
        $errorMessage = filled($payload['error_message'] ?? null) ? (string) $payload['error_message'] : null;

        $transaction = PaymentTransaction::query()
            ->with('order')
            ->where('provider', self::INTEGRATION)
            ->where('external_session_id', $sessionId)
            ->first();

        if ($transaction === null) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_rejected_transaction_not_found',
                status: 'warning',
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                ],
                errorMessage: 'Nie znaleziono transakcji platnosci dla przekazanego session_id.',
            );

            throw ValidationException::withMessages([
                'session_id' => 'Nie znaleziono transakcji platnosci dla przekazanego session_id.',
            ]);
        }

        $order = $transaction->order;

        if ($order === null || $order->number !== $orderNumber) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_rejected_order_mismatch',
                status: 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                ],
                errorMessage: 'Callback platnosci nie pasuje do numeru zamowienia przypisanego do transakcji.',
                metadata: [
                    'expected_order_number' => $order?->number,
                    'received_order_number' => $orderNumber,
                ],
            );

            throw ValidationException::withMessages([
                'order_number' => 'Callback platnosci nie pasuje do numeru zamowienia przypisanego do transakcji.',
            ]);
        }

        $targetTransactionStatus = $incomingStatus === 'paid' ? 'confirmed' : 'failed';
        $targetOrderPaymentStatus = $incomingStatus === 'paid' ? 'paid' : 'failed';

        if (in_array($transaction->status, ['confirmed', 'failed'], true)) {
            if ($transaction->status === $targetTransactionStatus) {
                $this->integrationLogService->record(
                    integration: self::INTEGRATION,
                    event: 'payment_callback_replayed',
                    status: $targetTransactionStatus === 'confirmed' ? 'success' : 'warning',
                    order: $order,
                    direction: 'incoming',
                    externalReference: $sessionId,
                    requestPayload: $payload,
                    responsePayload: [
                        'outcome' => 'replayed',
                        'transaction_status' => $transaction->status,
                    ],
                    errorMessage: $transaction->error_message,
                    metadata: [
                        'payment_transaction_id' => $transaction->id,
                        'provider_status' => $providerStatus,
                    ],
                );

                return [
                    'transaction' => $transaction,
                    'order' => $order,
                    'replayed' => true,
                ];
            }

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_callback_rejected_conflicting_status',
                status: 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'outcome' => 'rejected',
                    'transaction_status' => $transaction->status,
                ],
                errorMessage: 'Otrzymano sprzeczny callback dla transakcji juz zakonczonej.',
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            throw ValidationException::withMessages([
                'status' => 'Otrzymano sprzeczny callback dla transakcji juz zakonczonej.',
            ]);
        }

        return DB::transaction(function () use ($transaction, $order, $payload, $sessionId, $targetTransactionStatus, $targetOrderPaymentStatus, $providerStatus, $errorMessage): array {
            $responsePayload = $transaction->response_payload ?? [];
            $responsePayload['callback'] = [
                'status' => $targetOrderPaymentStatus,
                'provider_status' => $providerStatus,
                'received_at' => now()->toIso8601String(),
            ];

            $transaction->forceFill([
                'status' => $targetTransactionStatus,
                'response_payload' => $responsePayload,
                'error_message' => $targetTransactionStatus === 'failed' ? ($errorMessage ?? 'Platnosc zostala oznaczona jako nieudana przez callback.') : null,
                'confirmed_at' => $targetTransactionStatus === 'confirmed' ? ($transaction->confirmed_at ?? now()) : null,
                'failed_at' => $targetTransactionStatus === 'failed' ? ($transaction->failed_at ?? now()) : null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'callback_provider_status' => $providerStatus,
                ]),
            ])->save();

            $order->forceFill([
                'payment_status' => $targetOrderPaymentStatus,
            ])->save();

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: $targetTransactionStatus === 'confirmed' ? 'payment_callback_confirmed' : 'payment_callback_failed',
                status: $targetTransactionStatus === 'confirmed' ? 'success' : 'warning',
                order: $order,
                direction: 'incoming',
                externalReference: $sessionId,
                requestPayload: $payload,
                responsePayload: [
                    'transaction_status' => $transaction->status,
                    'order_payment_status' => $order->payment_status,
                ],
                errorMessage: $transaction->error_message,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $providerStatus,
                ],
            );

            return [
                'transaction' => $transaction->fresh(),
                'order' => $order->fresh(),
                'replayed' => false,
            ];
        });
    }
}