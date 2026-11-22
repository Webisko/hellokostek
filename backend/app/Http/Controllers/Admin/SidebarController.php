<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function saveOrder(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'groups' => ['sometimes', 'array', 'max:20'],
            'groups.*' => ['string', 'max:100'],
            'resources' => ['sometimes', 'array', 'max:50'],
            'resources.*.label' => ['required', 'string', 'max:100'],
            'resources.*.group' => ['nullable', 'string', 'max:100'],
        ]);

        $setting = StoreSetting::first() ?? new StoreSetting();
        $metadata = $setting->metadata ?? [];

        // Zapis grup
        $metadata['navigation_groups'] = collect($validated['groups'] ?? [])
            ->map(fn ($groupName, $index) => [
                'name' => $groupName,
                'label' => $groupName,
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();

        // Mapowanie etykiet zasobów na ich nazwy klas
        $defaultResources = app(\App\Support\StoreSettings::class)->defaultResourcesNavigation();

        $metadata['resources_navigation'] = collect($validated['resources'] ?? [])
            ->map(function ($item, $index) use ($defaultResources) {
                // Znajdź zasób w domyślnej konfiguracji po etykiecie (label)
                $matched = collect($defaultResources)->first(fn ($d) => $d['label'] === $item['label']);

                return [
                    'resource' => $matched ? $matched['resource'] : $item['label'] . 'Resource',
                    'label' => $item['label'],
                    'group' => $item['group'] ?? ($matched['group'] ?? 'Analityka & system'),
                    'sort_order' => ($index + 1) * 10,
                    'visible' => $matched ? $matched['visible'] : true,
                ];
            })
            ->all();

        $setting->metadata = $metadata;
        $setting->save();

        return response()->json(['success' => true]);
    }
}
