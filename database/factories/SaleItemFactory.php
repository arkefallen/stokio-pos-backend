<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $price = $this->faker->randomFloat(2, 10, 100);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_name' => function (array $attributes) {
                return Product::find($attributes['product_id'])?->name ?? 'Test Product';
            },
            'product_sku' => function (array $attributes) {
                return Product::find($attributes['product_id'])?->sku ?? 'SKU-TEST';
            },
            'quantity' => $quantity,
            'price' => $price,
            'cost_price' => $price * 0.8,
            'subtotal' => $quantity * $price,
        ];
    }
}
