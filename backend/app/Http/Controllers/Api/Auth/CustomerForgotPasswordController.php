<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class CustomerForgotPasswordController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc'],
        ]);

        $user = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim($validated['email']))])
            ->first();

        if (! $user?->isStaff()) {
            try {
                Password::sendResetLink([
                    'email' => $validated['email'],
                ]);
            } catch (\Throwable $e) {
                Log::error('Błąd wysyłki linku resetu hasła: ' . $e->getMessage(), [
                    'email' => $validated['email'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Jeśli konto istnieje, wysłaliśmy instrukcje resetu hasła.',
        ], 202);
    }
}