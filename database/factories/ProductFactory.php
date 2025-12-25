<?php

namespace Database\Factories;

use App\Modules\Auth\Models\User;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'sku' => $this->faker->unique()->ean13,
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'cost_price' => $this->faker->randomFloat(2, 5, 500),
            'min_stock' => $this->faker->numberBetween(5, 20),
            'stock_qty' => $this->faker->numberBetween(0, 100), // Note: This might need specific handling if not fillable
            'image_path' => null,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (Product $product) {
            //
        })->afterCreating(function (Product $product) {
            // Force update stock_qty since it's guarded
            if (isset($this->states['stock_qty'])) {
                // But wait, states are applied before creation usually? 
                // If stock_qty is passed to create(), it filters it out if guarded.
            }
            // We can force set stock_qty after creation if needed
            if ($product->stock_qty !== 0) {
                // It seems the 'stock_qty' in definition might be ignored during create() if guarded
                // So we manually set it here if we want random stock
                $product->forceFill(['stock_qty' => $product->stock_qty])->save();
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_qty' => 5,
            'min_stock' => 10,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_qty' => 0,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock_qty' => 50,
            'min_stock' => 10,
        ]);
    }
}
