<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaqItem;
use Illuminate\Http\JsonResponse;

class FaqIndexController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $items = FaqItem::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items->map(fn (FaqItem $item): array => [
                '@type' => 'Question',
                'name' => $item->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item->answer,
                ],
            ])->values()->all(),
        ];

        return response()->json([
            'data' => [
                'items' => $items->map(fn (FaqItem $item): array => [
                    'id' => $item->id,
                    'question' => $item->question,
                    'answer' => $item->answer,
                    'group_name' => $item->group_name,
                    'sort_order' => $item->sort_order,
                ])->all(),
                'schema_json_ld' => $schema,
            ],
        ]);
    }
}