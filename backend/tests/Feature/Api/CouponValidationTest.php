<?php

namespace Tests\Feature\Api;

use App\Models\Coupon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_percentage_coupon_correctly(): void
    {
        Coupon::create([
            'code' => 'PROMO10',
            'discount_type' => 'percentage',
            'value' => 10, // 10%
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/coupons/validate', [
            'code' => 'PROMO10',
            'subtotal' => 200, // 200 PLN
        ]);

        $response->assertOk()
            ->assertJsonPath('valid', true);

        $this->assertEquals(20.0, (float) $response->json('discount_amount'));
    }

    public function test_validates_fixed_coupon_stored_in_grosze_correctly(): void
    {
        Coupon::create([
            'code' => 'RABAT50',
            'discount_type' => 'fixed',
            'value' => 5000, // 50.00 PLN in grosze
            'minimum_subtotal_amount' => 10000, // 100.00 PLN in grosze
            'is_active' => true,
        ]);

        // Below minimum
        $responseLow = $this->postJson('/api/coupons/validate', [
            'code' => 'RABAT50',
            'subtotal' => 80, // 80 PLN
        ]);
        $responseLow->assertStatus(422)
            ->assertJsonPath('valid', false);

        // Above minimum
        $responseOk = $this->postJson('/api/coupons/validate', [
            'code' => 'RABAT50',
            'subtotal' => 150, // 150 PLN
        ]);
        $responseOk->assertOk()
            ->assertJsonPath('valid', true);

        $this->assertEquals(50.0, (float) $responseOk->json('discount_amount'));
    }
}
