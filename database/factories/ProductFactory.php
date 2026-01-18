<?php

namespace Database\Factories;

use App\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 500),
            'sale_price' => null,
            'category' => fake()->word(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'condition' => fake()->randomElement(['new', 'used', 'vintage']),
            'brand' => fake()->company(),
            'color' => fake()->colorName(),
            'images' => [],
            'stock_quantity' => fake()->numberBetween(0, 100),
            'reserved_quantity' => 0,
            'is_active' => true,
            'sku' => 'SKU-' . fake()->unique()->numerify('#####'),
        ];
    }
}
