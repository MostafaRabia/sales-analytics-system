<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'quantity'   => $this->faker->numberBetween(1, 10),
            'price'      => $this->faker->randomFloat(2, 1, 100),
            'date'       => $this->faker->dateTime(),
        ];
    }
}
