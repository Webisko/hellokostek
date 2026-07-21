<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_minimal_info_for_guest(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'app' => config('app.name'),
            ]);
    }

    public function test_health_endpoint_returns_full_info_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/health');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'app',
                'environment',
                'store' => ['name', 'currency', 'free_shipping_threshold'],
                'product_types',
                'customer_segments',
                'integrations',
            ]);
    }

    public function test_health_endpoint_returns_minimal_info_for_regular_user(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => false])->save();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/health');

        $response->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'app' => config('app.name'),
            ]);
    }
}
