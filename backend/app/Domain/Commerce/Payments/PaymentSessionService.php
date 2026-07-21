<?php

namespace App\Domain\Commerce\Payments;

use App\Domain\Operations\IntegrationLogService;
use App\Models\Order;
use App\Domain\Commerce\Payments\StripeApiClient;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentSessionService
{
    private const INTEGRATION = 'przelewy24';

    public function __construct(
        private readonly IntegrationLogService $integrationLogService,
        private readonly Przelewy24ApiClient $przelewy24ApiClient,
        private readonly StripeApiClient $stripeApiClient,
    ) {
    }

    public function initiate(Order $order, ?string $blikCode = null): PaymentTransaction
    {
        $order->loadMissing('latestPaymentTransaction');

        if ($order->status !== 'placed') {
            $this->recordRejectedAttempt(
                order: $order,
                event: 'payment_session_rejected_invalid_order_status',
                errorMessage: 'Sesje platnosci mozna utworzyc tylko dla zlozonego zamowienia.',
                requestPayload: [
                    'order_number' => $order->number,
                    'order_status' => $order->status,
                ],
                metadata: [
                    'payment_provider' => data_get($order->metadata, 'payment.provider'),
                ],
            );

            throw ValidationException::withMessages([
                'order' => 'Sesje platnosci mozna utworzyc tylko dla zlozonego zamowienia.',
            ]);
        }

        $provider = (string) data_get($order->metadata, 'payment.provider');
        if (!in_array($provider, ['przelewy24', 'stripe'], true)) {
            $this->recordRejectedAttempt(
                order: $order,
                event: 'payment_session_rejected_provider_mismatch',
                errorMessage: 'To zamowienie nie wymaga sesji Przelewy24 lub Stripe.',
                requestPayload: [
                    'order_number' => $order->number,
                    'requested_provider' => $provider,
                ],
            );

            throw ValidationException::withMessages([
                'payment' => 'To zamowienie nie wymaga sesji Przelewy24 lub Stripe.',
            ]);
        }

        if ($provider === 'stripe') {
            return $this->initiateStripe($order);
        }

        $existingTransaction = $order->latestPaymentTransaction;
        if ($existingTransaction && in_array($existingTransaction->status, ['initiated', 'configuration_required'], true) && $blikCode === null) {
            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_session_reused',
                status: $existingTransaction->status === 'configuration_required' ? 'warning' : 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: $existingTransaction->external_session_id,
                requestPayload: [
                    'order_number' => $order->number,
                    'reused' => true,
                ],
                responsePayload: [
                    'redirect_url' => $existingTransaction->redirect_url,
                    'next_action' => data_get($existingTransaction->response_payload, 'next_action'),
                ],
                errorMessage: $existingTransaction->error_message,
                metadata: [
                    'payment_transaction_id' => $existingTransaction->id,
                    'provider_status' => $existingTransaction->status,
                ],
            );

            return $existingTransaction;
        }

        $sessionId = 'p24-' . Str::lower((string) Str::uuid());
        if (! $this->przelewy24ApiClient->isConfigured()) {
            return $this->createConfigurationRequiredTransaction($order, $sessionId);
        }

        $requestPayload = $this->buildRegisterPayload($order, $sessionId);

        try {
            $registrationResponse = $this->przelewy24ApiClient->registerTransaction($requestPayload);
            $token = (string) data_get($registrationResponse, 'data.token');

            if (blank($token)) {
                throw new Przelewy24ApiException(
                    'Przelewy24 nie zwrocilo tokenu transakcji.',
                    $requestPayload,
                    $registrationResponse,
                );
            }

            $blikResponse = null;
            if ($blikCode !== null) {
                try {
                    $blikResponse = $this->przelewy24ApiClient->payByBlikCode($token, $blikCode);
                } catch (\Exception $e) {
                    throw new Przelewy24ApiException(
                        'Płatność BLIK Direct nie powiodła się: ' . $e->getMessage(),
                        $requestPayload,
                        $registrationResponse
                    );
                }
            }

            return $this->createInitiatedTransaction(
                order: $order,
                sessionId: $sessionId,
                requestPayload: $requestPayload,
                token: $token,
                registrationResponse: $registrationResponse,
                blikCode: $blikCode,
                blikResponse: $blikResponse
            );
        } catch (Przelewy24ApiException $exception) {
            return $this->createFailedTransaction($order, $sessionId, $requestPayload, $exception);
        }
    }

    private function createConfigurationRequiredTransaction(Order $order, string $sessionId): PaymentTransaction
    {
        $config = config('services.przelewy24');

        return DB::transaction(function () use ($order, $sessionId, $config): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'przelewy24',
                'status' => 'configuration_required',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $sessionId,
                'redirect_url' => null,
                'error_code' => 'p24_not_configured',
                'error_message' => 'Brakuje konfiguracji Przelewy24 w srodowisku aplikacji.',
                'request_payload' => [
                    'order_number' => $order->number,
                    'merchant_id' => $config['merchant_id'] ?? null,
                    'pos_id' => $config['pos_id'] ?? null,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'response_payload' => [
                    'redirect_url' => null,
                    'next_action' => 'configure_przelewy24_credentials',
                ],
                'initiated_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => false,
                ],
            ]);

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_session_configuration_required',
                status: 'warning',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: $transaction->error_message,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     * @param  array<string, mixed>  $registrationResponse
     */
    private function createInitiatedTransaction(
        Order $order,
        string $sessionId,
        array $requestPayload,
        string $token,
        array $registrationResponse,
        ?string $blikCode = null,
        ?array $blikResponse = null
    ): PaymentTransaction {
        $redirectUrl = $blikCode !== null ? null : $this->przelewy24ApiClient->redirectUrlForToken($token);
        $storedRequestPayload = $this->storedRequestPayload($requestPayload);
        if ($blikCode !== null) {
            $storedRequestPayload['blik_code'] = '******';
        }

        return DB::transaction(function () use ($order, $sessionId, $storedRequestPayload, $redirectUrl, $token, $registrationResponse, $blikCode, $blikResponse): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'przelewy24',
                'status' => 'initiated',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $sessionId,
                'redirect_url' => $redirectUrl,
                'error_code' => null,
                'error_message' => null,
                'request_payload' => $storedRequestPayload,
                'response_payload' => [
                    'redirect_url' => $redirectUrl,
                    'next_action' => $blikCode !== null ? 'await_blik_confirmation' : 'redirect_to_przelewy24',
                    'token' => $token,
                    'registration' => $registrationResponse,
                    'blik_response' => $blikResponse,
                ],
                'initiated_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => true,
                    'direct_blik' => $blikCode !== null,
                ],
            ]);

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_session_initiated',
                status: 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: null,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    private function createFailedTransaction(Order $order, string $sessionId, array $requestPayload, Przelewy24ApiException $exception): PaymentTransaction
    {
        $storedRequestPayload = $this->storedRequestPayload($requestPayload);
        $responsePayload = is_array($exception->responsePayload)
            ? $exception->responsePayload
            : ['raw' => $exception->responsePayload];

        return DB::transaction(function () use ($order, $sessionId, $storedRequestPayload, $responsePayload, $exception): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'przelewy24',
                'status' => 'failed',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $sessionId,
                'redirect_url' => null,
                'error_code' => 'p24_register_failed',
                'error_message' => $exception->getMessage(),
                'request_payload' => $storedRequestPayload,
                'response_payload' => [
                    'next_action' => 'retry_create_przelewy24_transaction',
                    'registration' => $responsePayload,
                ],
                'initiated_at' => now(),
                'failed_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => true,
                    'http_status' => $exception->statusCode,
                ],
            ]);

            $this->integrationLogService->record(
                integration: self::INTEGRATION,
                event: 'payment_session_registration_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: $transaction->error_message,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRegisterPayload(Order $order, string $sessionId): array
    {
        $address = $order->shipping_address ?? $order->billing_address ?? [];
        $country = Str::upper((string) ($address['country'] ?? 'PL'));
        $payload = array_filter([
            'merchantId' => $this->przelewy24ApiClient->merchantId(),
            'posId' => $this->przelewy24ApiClient->posId(),
            'sessionId' => $sessionId,
            'amount' => (int) $order->total_amount,
            'currency' => (string) $order->currency,
            'description' => Str::limit(sprintf('%s zamowienie %s', config('shop.store.name', 'Sklep'), $order->number), 1024, ''),
            'email' => Str::limit((string) $order->customer_email, 50, ''),
            'client' => Str::limit(trim($order->customer_first_name . ' ' . $order->customer_last_name), 40, ''),
            'address' => Str::limit((string) ($address['line_1'] ?? $address['line1'] ?? ''), 80, ''),
            'zip' => Str::limit((string) ($address['postal_code'] ?? $address['postcode'] ?? ''), 10, ''),
            'city' => Str::limit((string) ($address['city'] ?? ''), 50, ''),
            'country' => Str::limit($country !== '' ? $country : 'PL', 2, ''),
            'phone' => $this->normalizedPhone($order->customer_phone, $country !== '' ? $country : 'PL'),
            'language' => 'pl',
            'urlReturn' => $this->returnUrl($order),
            'urlStatus' => route('api.integrations.przelewy24.payment-callback'),
            'waitForResult' => false,
            'regulationAccept' => false,
            'shipping' => (int) $order->shipping_amount,
            'encoding' => 'UTF-8',
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $payload['sign'] = $this->przelewy24ApiClient->makeRegisterSign(
            $sessionId,
            (int) $order->total_amount,
            (string) $order->currency,
        );

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function storedRequestPayload(array $payload): array
    {
        unset($payload['sign']);

        return $payload;
    }

    private function returnUrl(Order $order): string
    {
        $storefrontUrl = rtrim((string) config('services.storefront.url'), '/');

        return $storefrontUrl . '/zamowienie?number=' . urlencode($order->number);
    }

    private function normalizedPhone(?string $phone, string $country): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if ($country === 'PL' && strlen($digits) === 9) {
            return '48' . $digits;
        }

        return substr($digits, 0, 12);
    }

    private function recordRejectedAttempt(
        Order $order,
        string $event,
        string $errorMessage,
        array $requestPayload,
        ?array $metadata = null,
    ): void {
        $this->integrationLogService->record(
            integration: self::INTEGRATION,
            event: $event,
            status: 'warning',
            order: $order,
            direction: 'outgoing',
            externalReference: $order->number,
            requestPayload: $requestPayload,
            responsePayload: [
                'outcome' => 'rejected',
            ],
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    private function initiateStripe(Order $order): PaymentTransaction
    {
        $existingTransaction = $order->latestPaymentTransaction;
        if ($existingTransaction && in_array($existingTransaction->status, ['initiated', 'configuration_required'], true)) {
            $this->integrationLogService->record(
                integration: 'stripe',
                event: 'payment_session_reused',
                status: $existingTransaction->status === 'configuration_required' ? 'warning' : 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: $existingTransaction->external_session_id,
                requestPayload: [
                    'order_number' => $order->number,
                    'reused' => true,
                ],
                responsePayload: [
                    'redirect_url' => $existingTransaction->redirect_url,
                    'next_action' => data_get($existingTransaction->response_payload, 'next_action'),
                ],
                errorMessage: $existingTransaction->error_message,
                metadata: [
                    'payment_transaction_id' => $existingTransaction->id,
                    'provider_status' => $existingTransaction->status,
                ],
            );

            return $existingTransaction;
        }

        $sessionId = 'stripe-' . Str::lower((string) Str::uuid());
        if (! $this->stripeApiClient->isConfigured()) {
            return $this->createStripeConfigurationRequiredTransaction($order, $sessionId);
        }

        try {
            $client = $this->stripeApiClient->getClient();
            $stripeSession = $client->checkout->sessions->create([
                'payment_method_types' => ['card', 'blik', 'p24'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'product_data' => [
                            'name' => 'Zamówienie ' . $order->number,
                        ],
                        'unit_amount' => $order->total_amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->returnUrl($order) . '&stripe_status=success',
                'cancel_url' => $this->returnUrl($order) . '&stripe_status=cancelled',
                'metadata' => [
                    'order_number' => $order->number,
                ],
                'client_reference_id' => $sessionId,
            ]);

            $token = $stripeSession->id;
            $redirectUrl = $stripeSession->url;

            if (blank($token)) {
                throw new \Exception('Stripe nie zwrocil tokenu transakcji.');
            }

            return $this->createStripeInitiatedTransaction(
                order: $order,
                sessionId: $sessionId,
                token: $token,
                redirectUrl: $redirectUrl,
                stripeSession: $stripeSession->toArray()
            );
        } catch (\Exception $exception) {
            return $this->createStripeFailedTransaction($order, $sessionId, $exception);
        }
    }

    private function createStripeConfigurationRequiredTransaction(Order $order, string $sessionId): PaymentTransaction
    {
        $config = config('services.stripe');

        return DB::transaction(function () use ($order, $sessionId, $config): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'status' => 'configuration_required',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $sessionId,
                'redirect_url' => null,
                'error_code' => 'stripe_not_configured',
                'error_message' => 'Brakuje konfiguracji Stripe w srodowisku aplikacji.',
                'request_payload' => [
                    'order_number' => $order->number,
                    'key' => $config['key'] ?? null,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'response_payload' => [
                    'redirect_url' => null,
                    'next_action' => 'configure_stripe_credentials',
                ],
                'initiated_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => false,
                ],
            ]);

            $this->integrationLogService->record(
                integration: 'stripe',
                event: 'stripe_payment_session_failed',
                status: 'warning',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: $transaction->error_message,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    private function createStripeInitiatedTransaction(Order $order, string $sessionId, string $token, string $redirectUrl, array $stripeSession): PaymentTransaction
    {
        return DB::transaction(function () use ($order, $sessionId, $token, $redirectUrl, $stripeSession): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'status' => 'initiated',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $token,
                'redirect_url' => $redirectUrl,
                'error_code' => null,
                'error_message' => null,
                'request_payload' => [
                    'order_number' => $order->number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'response_payload' => [
                    'redirect_url' => $redirectUrl,
                    'next_action' => 'redirect_to_stripe',
                    'token' => $token,
                    'registration' => $stripeSession,
                ],
                'initiated_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => true,
                    'stripe_session_id' => $token,
                ],
            ]);

            $this->integrationLogService->record(
                integration: 'stripe',
                event: 'stripe_payment_session_initiated',
                status: 'success',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: null,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    private function createStripeFailedTransaction(Order $order, string $sessionId, \Exception $exception): PaymentTransaction
    {
        return DB::transaction(function () use ($order, $sessionId, $exception): PaymentTransaction {
            $transaction = PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'provider' => 'stripe',
                'status' => 'failed',
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'external_session_id' => $sessionId,
                'redirect_url' => null,
                'error_code' => 'stripe_register_failed',
                'error_message' => $exception->getMessage(),
                'request_payload' => [
                    'order_number' => $order->number,
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                ],
                'response_payload' => [
                    'next_action' => 'retry_create_stripe_transaction',
                ],
                'initiated_at' => now(),
                'failed_at' => now(),
                'metadata' => [
                    'order_number' => $order->number,
                    'configured' => true,
                ],
            ]);

            $this->integrationLogService->record(
                integration: 'stripe',
                event: 'stripe_payment_session_failed',
                status: 'error',
                order: $order,
                direction: 'outgoing',
                externalReference: $sessionId,
                requestPayload: $transaction->request_payload,
                responsePayload: $transaction->response_payload,
                errorMessage: $transaction->error_message,
                metadata: [
                    'payment_transaction_id' => $transaction->id,
                    'provider_status' => $transaction->status,
                ],
            );

            return $transaction;
        });
    }

    public function refundOrder(Order $order): void
    {
        $order->loadMissing('latestPaymentTransaction');
        $transaction = $order->latestPaymentTransaction;

        if (!$transaction || $transaction->status !== 'confirmed') {
            return;
        }

        $provider = $transaction->provider;
        $amount = $transaction->amount;

        if ($provider === 'stripe') {
            try {
                if (!$this->stripeApiClient->isConfigured()) {
                    return;
                }

                $stripe = $this->stripeApiClient->getClient();
                $stripeSessionId = $transaction->external_session_id;
                $paymentIntentId = null;

                if (str_starts_with($stripeSessionId, 'cs_')) {
                    $session = $stripe->checkout->sessions->retrieve($stripeSessionId);
                    $paymentIntentId = $session->payment_intent;
                } else {
                    $paymentIntentId = $stripeSessionId;
                }

                if ($paymentIntentId) {
                    $refund = $stripe->refunds->create([
                        'payment_intent' => $paymentIntentId,
                    ]);

                    $transaction->forceFill([
                        'status' => 'refunded',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'refund_id' => $refund->id,
                            'refunded_at' => now()->toIso8601String(),
                        ]),
                    ])->save();

                    $order->forceFill([
                        'payment_status' => 'refunded',
                    ])->save();

                    $this->integrationLogService->record(
                        integration: 'stripe',
                        event: 'payment_refund_success',
                        status: 'success',
                        order: $order,
                        direction: 'outgoing',
                        externalReference: $refund->id,
                        requestPayload: ['payment_intent' => $paymentIntentId],
                        responsePayload: $refund->toArray(),
                    );
                }
            } catch (\Exception $e) {
                $this->integrationLogService->record(
                    integration: 'stripe',
                    event: 'payment_refund_failed',
                    status: 'error',
                    order: $order,
                    direction: 'outgoing',
                    externalReference: $transaction->external_session_id,
                    requestPayload: ['amount' => $amount],
                    errorMessage: $e->getMessage(),
                );
            }
        } elseif ($provider === 'przelewy24') {
            try {
                if (!$this->przelewy24ApiClient->isConfigured()) {
                    return;
                }

                $p24OrderId = data_get($transaction->metadata, 'p24_order_id');
                if ($p24OrderId) {
                    $requestId = (string) Str::uuid();
                    $signSource = $p24OrderId . "|" . $transaction->external_session_id . "|" . $amount . "|" . $this->przelewy24ApiClient->crc();
                    $sign = hash('sha384', $signSource);

                    $refundPayload = [
                        'requestId' => $requestId,
                        'refunds' => [
                            [
                                'orderId' => (int) $p24OrderId,
                                'sessionId' => $transaction->external_session_id,
                                'amount' => (int) $amount,
                                'sign' => $sign,
                            ]
                        ],
                    ];

                    $refundResponse = $this->przelewy24ApiClient->refundTransaction($refundPayload);

                    $transaction->forceFill([
                        'status' => 'refunded',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'refund_response' => $refundResponse,
                            'refunded_at' => now()->toIso8601String(),
                        ]),
                    ])->save();

                    $order->forceFill([
                        'payment_status' => 'refunded',
                    ])->save();

                    $this->integrationLogService->record(
                        integration: 'przelewy24',
                        event: 'payment_refund_success',
                        status: 'success',
                        order: $order,
                        direction: 'outgoing',
                        externalReference: $requestId,
                        requestPayload: $refundPayload,
                        responsePayload: $refundResponse,
                    );
                }
            } catch (\Exception $e) {
                $this->integrationLogService->record(
                    integration: 'przelewy24',
                    event: 'payment_refund_failed',
                    status: 'error',
                    order: $order,
                    direction: 'outgoing',
                    externalReference: $transaction->external_session_id,
                    requestPayload: ['amount' => $amount],
                    errorMessage: $e->getMessage(),
                );
            }
        }
    }
}