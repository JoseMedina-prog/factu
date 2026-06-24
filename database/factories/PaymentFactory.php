<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $client = \App\Models\Client::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        return [
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_id' => null,
            'created_by' => $user->id,
            'amount' => fake()->randomFloat(2, 50, 5000),
            'payment_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'method' => fake()->randomElement(array_keys(Payment::METHODS)),
            'reference' => fake()->optional(0.7)->bothify('REF-####'),
            'status' => Payment::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn () => [
            'tenant_id' => $invoice->tenant_id,
            'client_id' => $invoice->client_id,
            'invoice_id' => $invoice->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_PENDING,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => Payment::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);
    }
}