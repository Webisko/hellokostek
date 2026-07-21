<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    protected $model = ProductCategory::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug'            => Str::slug($name) . '-' . Str::random(4),
            'name'            => ucfirst($name),
            'description'     => fake()->sentence(),
            'seo_title'       => null,
            'seo_description' => null,
            'sort_order'      => fake()->numberBetween(0, 100),
            'is_active'       => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
