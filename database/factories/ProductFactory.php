<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $isProduct = fake()->boolean(70);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 1000000),
            'tax' => fake()->randomElement([0, 5, 10, 15, 19]),
            'type' => $isProduct ? Product::TYPE_PRODUCT : Product::TYPE_SERVICE,
            'is_active' => true,
            'stock' => $isProduct ? fake()->numberBetween(10, 500) : 0,
            'min_stock' => $isProduct ? fake()->numberBetween(5, 50) : 0,
            'cost' => fake()->randomFloat(2, 5000, 500000),
            'track_inventory' => $isProduct,
            'unit_of_measure' => fake()->randomElement(array_keys(Product::UNITS)),
            'sku' => fake()->optional(0.7)->bothify('SKU-#####'),
        ];
    }

    public function service(): static
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_SERVICE,
            'stock' => 0,
            'min_stock' => 0,
            'cost' => 0,
            'track_inventory' => false,
        ]);
    }

    public function withStock(float $stock, float $minStock = 0): static
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_PRODUCT,
            'stock' => $stock,
            'min_stock' => $minStock,
            'track_inventory' => true,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_PRODUCT,
            'track_inventory' => true,
            'stock' => 5,
            'min_stock' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => [
            'type' => Product::TYPE_PRODUCT,
            'track_inventory' => true,
            'stock' => 0,
            'min_stock' => 5,
        ]);
    }
}