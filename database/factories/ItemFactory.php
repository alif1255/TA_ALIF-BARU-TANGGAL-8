<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->realText(25, 2),
            'code' => fake()->unique()->regexify('[A-Z]{4}[0-9]{4}'),
            'category_id' => Category::factory(),
            'cost_price' => fake()->numberBetween(1000, 10000),
            'selling_price' => fake()->numberBetween(1000, 10000),
            'stock' => fake()->numberBetween(1, 100)
        ];
    }
}
