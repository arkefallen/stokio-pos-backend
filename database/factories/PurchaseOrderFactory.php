<?php

namespace Database\Factories;

use App\Modules\Auth\Models\User;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'purchase_number' => 'PO-' . $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'status' => PurchaseOrder::STATUS_PENDING,
            'ordered_at' => now(),
            'expected_delivery_date' => now()->addDays(7),
            'notes' => $this->faker->sentence,
            'created_by' => User::factory(),
        ];
    }

    public function ordered(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PurchaseOrder::STATUS_ORDERED,
        ]);
    }

    public function received(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PurchaseOrder::STATUS_RECEIVED,
            'received_at' => now(),
            'received_by' => User::factory(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => PurchaseOrder::STATUS_CANCELLED,
        ]);
    }
}
