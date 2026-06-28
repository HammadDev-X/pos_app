<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'image' => $this->faker->optional()->imageUrl(640, 480, 'products', true),
            'sku' => $this->faker->unique()->bothify('SKU-######'),
            'short_code' => $this->faker->boolean(30) ? $this->faker->unique()->bothify('P###') : null,
            'price' => $this->faker->randomFloat(2, 10, 999),
            'status' => $this->faker->boolean(),
            'quantity' => $this->faker->randomFloat(2, 0, 100),
            'unit' => 'pcs',
            'track_stock' => true,
            'is_quick_item' => false,
        ];
    }

    public function inactive(): ProductFactory|Factory
    {
        return $this->state(fn(array $attributes): array => [
            'status' => false,
        ]);
    }

    public function active(): ProductFactory|Factory
    {
        return $this->state(fn(array $attributes): array => [
            'status' => true,
        ]);
    }

    public function withOutImage(): ProductFactory|Factory
    {
        return $this->state(fn(array $attributes): array => [
            'image' => null,
        ]);
    }

    public function withOutDescription(): ProductFactory|Factory
    {
        return $this->state(fn(array $attributes): array => [
            'description' => null,
        ]);
    }

}
