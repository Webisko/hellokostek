<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationResendController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email jest już zweryfikowany.'
            ], 422);
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Błąd ponownej wysyłki linku weryfikacyjnego: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
            ]);
        }

        return response()->json([
            'message' => 'Link weryfikacyjny został wysłany ponownie.'
        ]);
    }
}
