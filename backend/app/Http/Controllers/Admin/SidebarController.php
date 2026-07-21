<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function saveOrder(Request $request)
    {
        $setting = StoreSetting::first() ?? new StoreSetting();
        $metadata = $setting->metadata ?? [];

        // Zapis grup
        $metadata['navigation_groups'] = collect($request->input('groups', []))
            ->map(fn ($groupName, $index) => [
                'name' => $groupName,
                'label' => $groupName,
                'sort_order' => ($index + 1) * 10,
            ])
            ->all();

        // Mapowanie etykiet zasobów na ich nazwy klas
        $defaultResources = app(\App\Support\StoreSettings::class)->defaultResourcesNavigation();
        
        $metadata['resources_navigation'] = collect($request->input('resources', []))
            ->map(function ($item, $index) use ($defaultResources) {
                // Znajdź zasób w domyślnej konfiguracji po etykiecie (label)
                $matched = collect($defaultResources)->first(fn ($d) => $d['label'] === $item['label']);
                
                return [
                    'resource' => $matched ? $matched['resource'] : $item['label'] . 'Resource',
                    'label' => $item['label'],
                    'group' => $item['group'],
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
