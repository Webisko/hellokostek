<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BackInStockSubscription;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackInStockSubscribeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $productId = $validated['product_id'];
        $variantId = $validated['product_variant_id'] ?? null;
        $email = $validated['email'];

        // If variant_id is provided, verify it belongs to the product
        if ($variantId) {
            $variantExists = ProductVariant::query()
                ->where('id', $variantId)
                ->where('product_id', $productId)
                ->exists();

            if (!$variantExists) {
                return response()->json([
                    'message' => 'Podany wariant nie należy do wskazanego produktu.',
                ], 422);
            }
        }

        // Verify if product (or variant) is actually out of stock, otherwise subscription is not needed
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant->manages_stock && $variant->stock_quantity > 0) {
                return response()->json([
                    'message' => 'Ten wariant jest obecnie dostępny na magazynie.',
                ], 422);
            }
        } else {
            $product = Product::find($productId);
            // If the product has variants, they must subscribe to a specific variant
            if ($product->variants()->exists()) {
                return response()->json([
                    'message' => 'Proszę wskazać konkretny wariant produktu do subskrypcji.',
                ], 422);
            }

            if ($product->manages_stock && $product->stock_quantity > 0) {
                return response()->json([
                    'message' => 'Ten produkt jest obecnie dostępny na magazynie.',
                ], 422);
            }
        }

        // Create or get subscription
        $subscription = BackInStockSubscription::query()
            ->firstOrCreate([
                'email' => $email,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'status' => 'pending',
            ]);

        return response()->json([
            'data' => $subscription,
            'message' => 'Pomyślnie zapisano na powiadomienie o dostępności.',
        ], 201);
    }
}
