<?php

namespace Database\Factories;

use App\Modules\Auth\Models\User;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'sale_number' => 'TRX-' . now()->format('Ymd') . '-' . $this->faker->unique()->numerify('####'),
            'status' => Sale::STATUS_COMPLETED,
            'payment_status' => Sale::PAYMENT_PAID,
            'payment_method' => Sale::METHOD_CASH,
            'subtotal' => $this->faker->randomFloat(2, 10, 500),
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal'];
            },
            'cash_given' => function (array $attributes) {
                return $attributes['total_amount'] + 10;
            },
            'change_return' => 10,
            'notes' => $this->faker->sentence,
            'created_by' => User::factory(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Sale::STATUS_PENDING,
            'payment_status' => Sale::PAYMENT_UNPAID,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Sale::STATUS_CANCELLED,
        ]);
    }
}
