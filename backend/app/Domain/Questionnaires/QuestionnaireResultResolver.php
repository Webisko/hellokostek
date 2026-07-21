<?php

namespace App\Domain\Questionnaires;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class QuestionnaireResultResolver
{
    public function resolve(string $questionnaireKey, array $answers): array
    {
        $definition = config("curandera.questionnaires.{$questionnaireKey}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unsupported questionnaire [{$questionnaireKey}].");
        }

        $productDefinitions = Arr::get($definition, 'products', []);
        $limit = (int) Arr::get($definition, 'result_limit', 2);
        $defaultProducts = Arr::get($definition, 'default_products', []);

        $scores = collect(array_keys($productDefinitions))
            ->mapWithKeys(fn (string $slug, int $index): array => [$slug => ['score' => 0, 'priority' => $index]])
            ->all();

        foreach ($answers as $questionKey => $selectedOptions) {
            $selectedOptions = is_array($selectedOptions) ? $selectedOptions : [$selectedOptions];

            foreach ($selectedOptions as $optionKey) {
                $optionScores = Arr::get($definition, sprintf('scoring.%s.%s', (string) $questionKey, (string) $optionKey), []);

                if (! is_array($optionScores)) {
                    continue;
                }

                foreach ($optionScores as $productSlug => $points) {
                    if (! array_key_exists($productSlug, $scores)) {
                        continue;
                    }

                    $scores[$productSlug]['score'] += (int) $points;
                }
            }
        }

        $recommendedSlugs = collect($scores)
            ->sortBy([
                ['score', 'desc'],
                ['priority', 'asc'],
            ])
            ->filter(fn (array $entry): bool => $entry['score'] > 0)
            ->keys()
            ->take($limit)
            ->values();

        if ($recommendedSlugs->count() < $limit) {
            $recommendedSlugs = $recommendedSlugs
                ->concat(collect($defaultProducts)->diff($recommendedSlugs)->take($limit - $recommendedSlugs->count()))
                ->values();
        }

        $catalogProducts = Product::query()
            ->publicCatalog()
            ->with('categories:id,name,slug')
            ->whereIn('slug', $recommendedSlugs->all())
            ->get()
            ->keyBy('slug');

        $recommendations = $recommendedSlugs
            ->map(function (string $slug) use ($catalogProducts, $productDefinitions, $scores): array {
                /** @var Product|null $product */
                $product = $catalogProducts->get($slug);
                $productDefinition = $productDefinitions[$slug] ?? [];

                return [
                    'slug' => $slug,
                    'name' => $product?->name ?? Arr::get($productDefinition, 'name', $slug),
                    'score' => $scores[$slug]['score'] ?? 0,
                    'type' => $product?->type?->value,
                    'short_description' => $product?->short_description,
                    'currency' => $product?->currency,
                    'regular_price_amount' => $product?->regular_price_amount,
                    'sale_price_amount' => $product?->sale_price_amount,
                    'current_price_amount' => $product?->currentPriceAmount(),
                    'categories' => $product?->categories->map(fn (ProductCategory $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ])->all() ?? [],
                    'source' => $product ? 'catalog' : 'questionnaire_config',
                ];
            })
            ->all();

        return [
            'questionnaire_key' => $questionnaireKey,
            'coupon_code' => Arr::get($definition, 'coupon_code'),
            'recommendations' => $recommendations,
        ];
    }

    public function supportedQuestionnaireKeys(): array
    {
        $questionnaires = config('curandera.questionnaires', []);

        return is_array($questionnaires) ? array_keys($questionnaires) : [];
    }
}