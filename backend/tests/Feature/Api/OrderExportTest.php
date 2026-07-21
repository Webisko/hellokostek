<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_orders(): void
    {
        $response = $this->get(route('admin.exports.orders'));

        // Since it uses 'auth' middleware, guests are redirected to login
        $response->assertRedirect();
    }

    public function test_regular_user_cannot_export_orders(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => false])->save();

        $response = $this->actingAs($user)->get(route('admin.exports.orders'));

        $response->assertStatus(403);
    }

    public function test_admin_can_export_orders_as_csv(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        // Create an order to export
        $order = Order::query()->create([
            'number' => 'ORD-123456',
            'placed_at' => now(),
            'status' => 'placed',
            'payment_status' => 'paid',
            'fulfillment_status' => 'pending',
            'currency' => 'PLN',
            'total_amount' => 15000, // 150.00 PLN
            'subtotal_amount' => 12195, // 121.95 PLN
            'tax_amount' => 2805, // 28.05 PLN
            'discount_amount' => 0,
            'shipping_amount' => 1500, // 15.00 PLN
            'customer_email' => 'customer@example.com',
            'customer_first_name' => 'Jan',
            'customer_last_name' => 'Kowalski',
            'customer_phone' => '123456789',
            'wants_invoice' => true,
            'billing_company_name' => 'Firma XYZ',
            'billing_nip' => '1234567890',
            'billing_address' => [
                'street' => 'ul. Jasna 10',
                'postal_code' => '00-001',
                'city' => 'Warszawa',
                'country' => 'PL'
            ],
            'shipping_address' => [
                'street' => 'ul. Ciemna 5',
                'postal_code' => '00-002',
                'city' => 'Kraków',
                'country' => 'PL'
            ],
            'shipping_method_name' => 'Kurier DHL',
            'carrier' => 'DHL',
            'tracking_number' => '123456789012',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.exports.orders'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        $content = $response->streamedContent();

        // Verify CSV structure and values
        $this->assertStringContainsString("\xEF\xBB\xBF", $content); // BOM
        $this->assertStringContainsString('"Numer zamówienia"', $content);
        $this->assertStringContainsString('"Data złożenia"', $content);
        $this->assertStringContainsString('"Status zamówienia"', $content);
        $this->assertStringContainsString('ORD-123456', $content);
        $this->assertStringContainsString('customer@example.com', $content);
        $this->assertStringContainsString('Jan', $content);
        $this->assertStringContainsString('Kowalski', $content);
        $this->assertStringContainsString('Firma XYZ', $content);
        $this->assertStringContainsString('1234567890', $content);
        $this->assertStringContainsString('ul. Jasna 10, 00-001, Warszawa, PL', $content);
        $this->assertStringContainsString('ul. Ciemna 5, 00-002, Kraków, PL', $content);
        $this->assertStringContainsString('Kurier DHL', $content);
        $this->assertStringContainsString('150.00', $content);
        $this->assertStringContainsString('121.95', $content);
        $this->assertStringContainsString('28.05', $content);
    }
}
