<?php

namespace App\Domain\Commerce\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Przelewy24ApiClient
{
    public function isConfigured(bool $includeCallbackToken = false): bool
    {
        $config = $this->config();

        return (bool) ($config['enabled'] ?? false)
            && $this->missingConfigurationFields($includeCallbackToken) === [];
    }

    /**
     * @return array<int, string>
     */
    public function missingConfigurationFields(bool $includeCallbackToken = false): array
    {
        $fieldLabels = self::configurationFieldLabels($includeCallbackToken);
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
    public static function configurationFieldLabels(bool $includeCallbackToken = false): array
    {
        $fields = [
            'merchant_id' => 'Merchant ID',
            'pos_id' => 'POS ID',
            'crc' => 'CRC',
            'api_key' => 'API key',
            'api_base_url' => 'URL API',
        ];

        if ($includeCallbackToken) {
            $fields['callback_token'] = 'token callbacku';
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function registerTransaction(array $payload): array
    {
        return $this->request('POST', 'transaction/register', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function verifyTransaction(array $payload): array
    {
        return $this->request('PUT', 'transaction/verify', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function refundTransaction(array $payload): array
    {
        return $this->request('POST', 'transaction/refund', $payload);
    }

    /**
     * Submit a BLIK 6-digit code for a registered transaction token.
     *
     * @param string $token
     * @param string $blikCode
     * @return array<string, mixed>
     */
    public function payByBlikCode(string $token, string $blikCode): array
    {
        return $this->request('POST', 'transaction/byCode', [
            'token' => $token,
            'blikCode' => (int) $blikCode,
        ]);
    }

    public function redirectUrlForToken(string $token): string
    {
        $configuredBaseUrl = (string) ($this->config()['redirect_base_url'] ?? '');
        $baseUrl = filled($configuredBaseUrl)
            ? rtrim($configuredBaseUrl, '/')
            : $this->derivedRedirectBaseUrl();

        return $baseUrl . '/' . rawurlencode($token);
    }

    public function notificationMatchesConfiguredMerchant(array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return (int) ($payload['merchantId'] ?? 0) === $this->merchantId()
            && (int) ($payload['posId'] ?? 0) === $this->posId();
    }

    public function notificationSignatureIsValid(array $payload): bool
    {
        $receivedSignature = (string) ($payload['sign'] ?? '');

        if ($receivedSignature === '') {
            return false;
        }

        return hash_equals($this->makeNotificationSign($payload), $receivedSignature);
    }

    public function makeRegisterSign(string $sessionId, int $amount, string $currency): string
    {
        return $this->signHash([
            'sessionId' => $sessionId,
            'merchantId' => $this->merchantId(),
            'amount' => $amount,
            'currency' => $currency,
            'crc' => $this->crc(),
        ]);
    }

    public function makeVerifySign(string $sessionId, int $orderId, int $amount, string $currency): string
    {
        return $this->signHash([
            'sessionId' => $sessionId,
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'crc' => $this->crc(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function makeNotificationSign(array $payload): string
    {
        return $this->signHash([
            'merchantId' => (int) $payload['merchantId'],
            'posId' => (int) $payload['posId'],
            'sessionId' => (string) $payload['sessionId'],
            'amount' => (int) $payload['amount'],
            'originAmount' => (int) $payload['originAmount'],
            'currency' => (string) $payload['currency'],
            'orderId' => (int) $payload['orderId'],
            'methodId' => (int) $payload['methodId'],
            'statement' => (string) $payload['statement'],
            'crc' => $this->crc(),
        ]);
    }

    public function merchantId(): int
    {
        return (int) ($this->config()['merchant_id'] ?? 0);
    }

    public function posId(): int
    {
        return (int) ($this->config()['pos_id'] ?? 0);
    }

    public function crc(): string
    {
        return (string) ($this->config()['crc'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload): array
    {
        $url = rtrim((string) ($this->config()['api_base_url'] ?? ''), '/') . '/' . ltrim($path, '/');

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(15)
                ->withBasicAuth((string) $this->posId(), (string) ($this->config()['api_key'] ?? ''))
                ->send($method, $url, [
                    'json' => $payload,
                ]);
        } catch (ConnectionException $exception) {
            throw new Przelewy24ApiException(
                'Nie udalo sie polaczyc z API Przelewy24.',
                $payload,
                null,
                null,
                $exception,
            );
        }

        $responsePayload = $response->json();
        $normalizedResponsePayload = is_array($responsePayload) ? $responsePayload : ['raw' => $response->body()];

        if (! $response->successful()) {
            throw new Przelewy24ApiException(
                $this->errorMessage($normalizedResponsePayload, 'Przelewy24 zwrocilo blad HTTP podczas wywolania API.'),
                $payload,
                $normalizedResponsePayload,
                $response->status(),
            );
        }

        if ((int) data_get($normalizedResponsePayload, 'responseCode', 1) !== 0) {
            throw new Przelewy24ApiException(
                $this->errorMessage($normalizedResponsePayload, 'Przelewy24 odrzucilo zadanie API.'),
                $payload,
                $normalizedResponsePayload,
                $response->status(),
            );
        }

        return $normalizedResponsePayload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function signHash(array $payload): string
    {
        return hash('sha384', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return (array) config('services.przelewy24', []);
    }

    private function derivedRedirectBaseUrl(): string
    {
        $parts = parse_url((string) ($this->config()['api_base_url'] ?? ''));

        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? 'secure.przelewy24.pl';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $host . $port . '/trnRequest';
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function errorMessage(array $payload, string $fallback): string
    {
        return (string) (
            data_get($payload, 'error')
            ?? data_get($payload, 'message')
            ?? data_get($payload, 'data.error')
            ?? data_get($payload, 'data.message')
            ?? $fallback
        );
    }
}