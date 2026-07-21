<?php

namespace Database\Factories;

use App\Domain\Commerce\Enums\ProductType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'slug'                 => Str::slug($name) . '-' . Str::random(4),
            'sku'                  => strtoupper(Str::random(3)) . '-' . fake()->numberBetween(100, 999),
            'type'                 => ProductType::Physical,
            'name'                 => ucfirst($name),
            'short_description'    => fake()->sentence(),
            'description'          => fake()->paragraph(),
            'currency'             => 'PLN',
            'regular_price_amount' => fake()->numberBetween(1000, 50000),
            'sale_price_amount'    => null,
            'stock_quantity'       => fake()->numberBetween(10, 100),
            'manages_stock'        => true,
            'is_active'            => true,
            'is_visible'           => true,
            'is_purchasable'       => true,
            'is_new'               => false,
            'is_bestseller'        => false,
            'is_recommended'       => false,
            'is_promoted'          => false,
            'is_seasonal'          => false,
            'is_clearance'         => false,
            'show_on_homepage'     => false,
            'show_in_bestsellers'  => false,
            'show_in_new_arrivals' => false,
            'show_in_recommended'  => false,
            'seo_title'            => null,
            'seo_description'      => null,
            'published_at'         => now()->subHour(),
            'metadata'             => null,
            'manual_tags'          => null,
        ];
    }

    /** Product that is visible in the public catalog. */
    public function public(): static
    {
        return $this->state([
            'is_active'      => true,
            'is_visible'     => true,
            'is_purchasable' => true,
            'published_at'   => now()->subHour(),
        ]);
    }

    /** Product that is hidden from the public catalog. */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** Digital product. */
    public function digital(): static
    {
        return $this->state([
            'type'          => ProductType::Digital,
            'manages_stock' => false,
            'stock_quantity' => null,
        ]);
    }

    /** Service product. */
    public function service(): static
    {
        return $this->state([
            'type'          => ProductType::Service,
            'manages_stock' => false,
            'stock_quantity' => null,
        ]);
    }
}
