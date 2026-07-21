<?php

namespace App\Http\Controllers\Api\Auth;

use App\Domain\Customers\CustomerAccountService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CustomerLoginController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $customerAccountService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->with('customerProfile')
            ->where('is_admin', false) // Admins authenticate via Filament panel only
            ->whereRaw('lower(email) = ?', [mb_strtolower($validated['email'])])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Dane logowania sa nieprawidlowe.',
            ]);
        }

        $this->customerAccountService->linkOrdersToUser($user);
        $this->customerAccountService->syncProfile($user->load('customerProfile'));

        $token = $user->createToken('storefront')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'segment' => $user->segment()->value,
                ],
            ],
        ]);
    }
}