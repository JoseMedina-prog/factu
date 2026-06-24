<?php

namespace Database\Factories;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => InventoryMovement::TYPE_ENTRY,
            'quantity' => fake()->numberBetween(1, 50),
            'stock_before' => 0,
            'stock_after' => fake()->numberBetween(1, 50),
            'unit_cost' => fake()->randomFloat(2, 100, 10000),
            'total_cost' => 0,
            'reason' => fake()->optional(0.5)->sentence(),
            'movement_date' => now()->toDateString(),
        ];
    }

    public function entry(): static
    {
        return $this->state(fn () => ['type' => InventoryMovement::TYPE_ENTRY]);
    }

    public function exit(): static
    {
        return $this->state(fn () => ['type' => InventoryMovement::TYPE_EXIT]);
    }
}