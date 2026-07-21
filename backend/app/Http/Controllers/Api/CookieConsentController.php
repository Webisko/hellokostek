<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CookieConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CookieConsentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_token' => ['required', 'string', 'max:255'],
            'consent_choices' => ['required', 'array'],
            'consent_choices.necessary' => ['required', 'boolean'],
            'consent_choices.analytics' => ['required', 'boolean'],
            'consent_choices.functional' => ['required', 'boolean'],
            'consent_choices.marketing' => ['required', 'boolean'],
            'banner_version' => ['required', 'string', 'max:255'],
        ]);

        $cookieConsent = CookieConsent::query()->create([
            'consent_token' => $validated['consent_token'],
            'consent_choices' => $validated['consent_choices'],
            'banner_version' => $validated['banner_version'],
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'data' => $cookieConsent,
            'message' => 'Zgoda na pliki cookies została zapisana.',
        ], 201);
    }
}
