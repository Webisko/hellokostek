<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Auth::user()->wishlistProducts()
            ->select('products.*')
            ->publicCatalog()
            ->get();

        // Re-use CatalogController payload structure where possible (or simple representation)
        $payload = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'name' => $product->name,
            'current_price_amount' => $product->currentPriceAmount(),
            'regular_price_amount' => $product->regular_price_amount,
            'sale_price_amount' => $product->sale_price_amount,
            'featured_image_url' => $product->featuredImageUrl(),
        ])->all();

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        
        // Sync without detaching (meaning it won't duplicate, but will ensure it exists)
        $user->wishlistProducts()->syncWithoutDetaching([$validated['product_id']]);

        return response()->json([
            'message' => 'Produkt został dodany do listy życzeń.',
        ], 201);
    }

    public function destroy(int $productId): JsonResponse
    {
        $user = Auth::user();
        $user->wishlistProducts()->detach($productId);

        return response()->json([
            'message' => 'Produkt został usunięty z listy życzeń.',
        ]);
    }
}
