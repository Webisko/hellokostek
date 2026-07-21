<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Domain\Commerce\Enums\UserRole;
use App\Filament\Pages\StoreDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_filament_panel(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($customer)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_filament_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)->get(StoreDashboard::getUrl());

        $response->assertOk();
    }

    public function test_manager_can_access_filament_panel(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);

        $response = $this->actingAs($manager)->get(StoreDashboard::getUrl());

        $response->assertOk();
    }

    public function test_employee_can_access_filament_panel(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Employee]);

        $response = $this->actingAs($employee)->get(StoreDashboard::getUrl());

        $response->assertOk();
    }

    public function test_employee_cannot_delete_products_but_admin_can(): void
    {
        $product = Product::factory()->create();
        $employee = User::factory()->create(['role' => UserRole::Employee]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertFalse($employee->can('delete', $product));
        $this->assertTrue($admin->can('delete', $product));
    }
}
