<?php

namespace Database\Factories;

use App\Models\FaqItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FaqItem>
 */
class FaqItemFactory extends Factory
{
    protected $model = FaqItem::class;

    public function definition(): array
    {
        return [
            'question'   => fake()->sentence() . '?',
            'answer'     => fake()->paragraph(),
            'group_name' => fake()->randomElement(['Zamówienia', 'Zakupy', 'Konsultacje', 'Dostawa', null]),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active'  => true,
            'metadata'   => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
