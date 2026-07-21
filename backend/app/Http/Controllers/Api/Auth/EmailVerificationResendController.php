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

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Link weryfikacyjny został wysłany ponownie.'
        ]);
    }
}
