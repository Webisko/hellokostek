<?php

namespace Tests\Feature\Api;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_and_add_to_cart(): void
    {
        $product = Product::factory()->public()->create([
            'name' => 'Awesome Shirt',
            'slug' => 'awesome-shirt',
            'regular_price_amount' => 5000,
        ]);

        // 1. Get empty cart
        $response = $this->getJson(route('api.cart.show'));
        $response->assertOk();
        $this->assertNotNull($response->json('data.session_token'));
        $this->assertEmpty($response->json('data.items'));

        $sessionToken = $response->json('data.session_token');

        // 2. Add product to cart
        $response = $this->postJson(route('api.cart.items.store'), [
            'session_token' => $sessionToken,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $response->assertStatus(201);

        // 3. Verify cart items
        $response = $this->getJson(route('api.cart.show') . "?session_token={$sessionToken}");
        $response->assertOk();
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals(2, $response->json('data.items.0.quantity'));
        $this->assertEquals(10000, $response->json('data.items.0.total_price'));

        $itemId = $response->json('data.items.0.id');

        // 4. Update quantity
        $response = $this->putJson(route('api.cart.items.update', ['itemId' => $itemId]), [
            'session_token' => $sessionToken,
            'quantity' => 5,
        ]);
        $response->assertOk();

        // 5. Verify update
        $response = $this->getJson(route('api.cart.show') . "?session_token={$sessionToken}");
        $response->assertOk();
        $this->assertEquals(5, $response->json('data.items.0.quantity'));

        // 6. Delete item
        $response = $this->deleteJson(route('api.cart.items.destroy', ['itemId' => $itemId]), [
            'session_token' => $sessionToken,
        ]);
        $response->assertOk();

        // 7. Verify deletion
        $response = $this->getJson(route('api.cart.show') . "?session_token={$sessionToken}");
        $response->assertOk();
        $this->assertEmpty($response->json('data.items'));
    }

    public function test_guest_cart_merges_into_user_cart_upon_access(): void
    {
        $product1 = Product::factory()->public()->create(['name' => 'Shirt A', 'slug' => 'shirt-a']);
        $product2 = Product::factory()->public()->create(['name' => 'Shirt B', 'slug' => 'shirt-b']);

        // 1. Create a guest cart and add product1
        $response = $this->getJson(route('api.cart.show'));
        $sessionToken = $response->json('data.session_token');

        $this->postJson(route('api.cart.items.store'), [
            'session_token' => $sessionToken,
            'product_id' => $product1->id,
            'quantity' => 2,
        ])->assertStatus(201);

        // 2. Create a user cart and add product2
        $user = User::factory()->create();
        $userCart = Cart::query()->create(['user_id' => $user->id]);
        CartItem::query()->create([
            'cart_id' => $userCart->id,
            'product_id' => $product2->id,
            'quantity' => 3,
        ]);

        // 3. Access cart as user, passing guest session_token
        $response = $this->actingAs($user, 'sanctum')->getJson(route('api.cart.show') . "?session_token={$sessionToken}");
        $response->assertOk();

        // Guest cart should be merged and deleted
        $this->assertDatabaseMissing('carts', ['session_token' => $sessionToken]);

        // User cart should now contain both items
        $this->assertCount(2, $response->json('data.items'));
        $items = collect($response->json('data.items'));
        $shirtA = $items->firstWhere('product.id', $product1->id);
        $shirtB = $items->firstWhere('product.id', $product2->id);

        $this->assertNotNull($shirtA);
        $this->assertEquals(2, $shirtA['quantity']);

        $this->assertNotNull($shirtB);
        $this->assertEquals(3, $shirtB['quantity']);
    }
}
