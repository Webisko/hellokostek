<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpsHealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function signHealthRequest(string $token, ?int $timestamp = null): array
    {
        $ts = (string) ($timestamp ?? time());
        $payload = $ts . '.GET./api/_webisko-health.';
        $signature = hash_hmac('sha256', $payload, $token);

        return [
            'Authorization' => 'Bearer ' . $token,
            'X-Webisko-Timestamp' => $ts,
            'X-Webisko-Signature' => $signature,
            'Accept' => 'application/json',
        ];
    }

    public function test_health_endpoint_is_fail_closed_when_token_is_not_configured(): void
    {
        config(['services.ops.health_token' => '']);

        $this->get('/api/_webisko-health', ['Accept' => 'application/json'])
            ->assertStatus(503);
    }

    public function test_health_endpoint_rejects_missing_invalid_or_expired_hmac_requests(): void
    {
        $token = 'test-ops-health-token-12345';
        config(['services.ops.health_token' => $token]);

        $this->get('/api/_webisko-health', ['Accept' => 'application/json'])
            ->assertStatus(401);

        $this->get('/api/_webisko-health', [
            'Authorization' => 'Bearer ' . $token,
            'X-Webisko-Timestamp' => (string) time(),
            'X-Webisko-Signature' => str_repeat('0', 64),
            'Accept' => 'application/json',
        ])->assertStatus(401);

        $this->get(
            '/api/_webisko-health',
            $this->signHealthRequest($token, time() - 400)
        )->assertStatus(401);
    }

    public function test_health_endpoint_returns_versions_and_database_status_for_valid_hmac(): void
    {
        $token = 'test-ops-health-token-12345';
        config(['services.ops.health_token' => $token]);

        $response = $this->get(
            '/api/_webisko-health',
            $this->signHealthRequest($token)
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'app' => ['name', 'env', 'url', 'timezone'],
                'versions' => ['php', 'laravel'],
                'database' => ['ok', 'driver'],
                'disk' => ['free_mb', 'total_mb', 'free_percent'],
                'cron' => ['last_heartbeat_at'],
                'logs' => ['errors_24h'],
                'checked_at',
            ])
            ->assertJsonPath('database.ok', true);

        $raw = (string) $response->getContent();
        $this->assertStringNotContainsString($token, $raw);
    }
}
