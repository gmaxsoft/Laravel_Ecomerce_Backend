<?php

namespace Database\Factories;

use App\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???###')),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'value' => fake()->randomFloat(2, 5, 50),
            'minimum_amount' => fake()->randomFloat(2, 0, 100),
            'usage_limit' => fake()->numberBetween(10, 1000),
            'usage_count' => 0,
            'usage_limit_per_user' => null,
            'starts_at' => now()->subDays(7),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ];
    }
}
