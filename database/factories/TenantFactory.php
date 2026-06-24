<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'nit' => fake()->unique()->numerify('##########'),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
            'plan' => Tenant::PLAN_FREE,
            'plan_expires_at' => now()->addDays(30),
            'max_users' => 1,
            'max_invoices_per_month' => 20,
            'max_clients' => 50,
        ];
    }

    public function pro(): static
    {
        return $this->state(fn () => [
            'plan' => Tenant::PLAN_PRO,
            'plan_expires_at' => null,
            'max_users' => 999,
            'max_invoices_per_month' => 9999,
            'max_clients' => 9999,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'plan_expires_at' => now()->subDays(1),
        ]);
    }
}