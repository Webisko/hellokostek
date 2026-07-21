<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RedirectRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedirectResolveController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
        ]);

        $normalizedPath = '/' . ltrim((string) $validated['path'], '/');

        $rule = RedirectRule::query()
            ->active()
            ->where('source_path', $normalizedPath)
            ->firstOrFail();

        if (config('shop.seo.track_redirect_hits', true)) {
            $rule->forceFill([
                'hit_count' => $rule->hit_count + 1,
                'last_hit_at' => now(),
            ])->save();
        }

        return response()->json([
            'data' => [
                'redirect' => [
                    'source_path' => $rule->source_path,
                    'target_path' => $rule->target_path,
                    'status_code' => $rule->status_code,
                ],
            ],
        ]);
    }
}