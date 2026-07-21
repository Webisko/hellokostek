<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Customers\CustomerAccountService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerRegisterController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $customerAccountService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->uncompromised()],
            'phone' => ['nullable', 'string', 'max:50'],
            'marketing_consent' => ['nullable', 'boolean'],
        ]);

        $user = $this->customerAccountService->register($validated);

        event(new \Illuminate\Auth\Events\Registered($user));

        $token = $user->createToken('storefront')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
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
        ];
    }
}