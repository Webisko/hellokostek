<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountMeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('customerProfile');

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'segment' => $user->segment()->value,
                    'customer_profile' => [
                        'phone' => $user->customerProfile?->phone,
                        'completed_orders_count' => $user->customerProfile?->completed_orders_count ?? 0,
                        'marketing_consent_at' => optional($user->customerProfile?->marketing_consent_at)->toIso8601String(),
                        'last_order_at' => optional($user->customerProfile?->last_order_at)->toIso8601String(),
                    ],
                ],
            ],
        ]);
    }
}