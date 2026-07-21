<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user('sanctum');
        $sessionToken = $request->header('X-Cart-Session-Token') ?: $request->input('session_token');

        if ($user) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
            // If session token was provided, merge that guest cart into the user's cart
            if ($sessionToken) {
                $guestCart = Cart::query()->where('session_token', $sessionToken)->first();
                if ($guestCart && $guestCart->id !== $cart->id) {
                    $this->mergeCarts($guestCart, $cart);
                }
            }
            return $cart;
        }

        if (!$sessionToken) {
            $sessionToken = Str::uuid()->toString();
        }

        $cart = Cart::query()->firstOrCreate(['session_token' => $sessionToken]);
        return $cart;
    }

    private function mergeCarts(Cart $source, Cart $destination): void
    {
        foreach ($source->items as $item) {
            $existing = $destination->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
                $item->delete();
            } else {
                $item->update(['cart_id' => $destination->id]);
            }
        }
        $source->delete();
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);

        return response()->json([
            'data' => [
                'session_token' => $cart->session_token,
                'items' => $cart->items()->with(['product', 'variant'])->get()->map(fn (CartItem $item) => [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'price' => $item->product->currentPriceAmount(),
                        'featured_image_url' => $item->product->featuredImageUrl(),
                    ],
                    'variant' => $item->variant ? [
                        'id' => $item->variant->id,
                        'sku' => $item->variant->sku,
                        'price' => $item->variant->currentPriceAmount(),
                    ] : null,
                    'quantity' => $item->quantity,
                    'total_price' => ($item->variant ? $item->variant->currentPriceAmount() : $item->product->currentPriceAmount()) * $item->quantity,
                ])->all(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getOrCreateCart($request);

        $productId = $request->input('product_id');
        $variantId = $request->input('product_variant_id');
        $quantity = (int) $request->input('quantity');

        $item = $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($item) {
            $item->update(['quantity' => $item->quantity + $quantity]);
        } else {
            $item = $cart->items()->create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Produkt dodany do koszyka.'
        ], 201);
    }

    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);

        $item->update(['quantity' => (int) $request->input('quantity')]);

        return response()->json([
            'success' => true,
            'message' => 'Ilosc zostala zaktualizowana.'
        ]);
    }

    public function destroy(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->getOrCreateCart($request);
        $item = $cart->items()->findOrFail($itemId);

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produkt zostal usuniety z koszyka.'
        ]);
    }
}
