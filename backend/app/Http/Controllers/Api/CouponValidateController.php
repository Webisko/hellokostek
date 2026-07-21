<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponValidateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
        ]);

        $code = Str::upper(trim($validated['code']));
        $subtotal = (float) ($validated['subtotal'] ?? 0);

        $coupon = Coupon::query()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if (! $coupon || ! $coupon->is_active) {
            return response()->json([
                'valid' => false,
                'message' => 'Kod rabatowy nie istnieje lub jest nieaktywny.',
            ], 422);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return response()->json([
                'valid' => false,
                'message' => 'Kod rabatowy jeszcze nie jest aktywny.',
            ], 422);
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'Kod rabatowy stracił ważność.',
            ], 422);
        }

        if ($coupon->minimum_subtotal_amount !== null) {
            $minRequired = (float) $coupon->minimum_subtotal_amount;
            if ($subtotal < $minRequired) {
                return response()->json([
                    'valid' => false,
                    'message' => sprintf('Minimalna wartość zamówienia dla tego kuponu to %s PLN.', number_format($minRequired, 2, ',', ' ')),
                ], 422);
            }
        }

        if ($coupon->usage_limit !== null) {
            $usedCount = \App\Models\Order::query()
                ->where('coupon_id', $coupon->id)
                ->whereIn('status', ['placed', 'completed'])
                ->count();

            if ($usedCount >= $coupon->usage_limit) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Kupon wykorzystano już maksymalną liczbę razy.',
                ], 422);
            }
        }

        // Calculate discount
        $discountAmount = 0.0;
        $discountType = $coupon->discount_type;
        $value = (float) $coupon->value;

        if ($discountType === 'percentage') {
            $discountAmount = round(($subtotal * ($value / 100.0)), 2);
        } else {
            // fixed or fixed_cart
            $discountAmount = min($subtotal, $value);
        }

        return response()->json([
            'valid' => true,
            'code' => $coupon->code,
            'name' => $coupon->name,
            'discount_type' => $discountType,
            'value' => $value,
            'discount_amount' => $discountAmount,
            'message' => sprintf('Kupon rabatowy %s został naliczony!', $coupon->code),
        ]);
    }
}
