<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'barcode' => fake()->boolean(60) ? fake()->ean13() : null,
            'brand_id' => ProductBrand::factory(),
            'category_id' => ProductCategory::factory(),
            'presentation_id' => ProductPresentation::factory(),
            'public_sale_price' => fake()->randomFloat(2, 5, 500),
            'current_stock' => fake()->randomFloat(2, 0, 200),
            'purchase_cost' => fake()->randomFloat(2, 1, 400),
            'internal_sale_price' => fake()->randomFloat(2, 5, 500),
            'sale_commission' => fake()->randomFloat(2, 0, 50),
            'commission_type' => fake()->randomElement(['percent', 'amount']),
            'includes_tax' => fake()->boolean(),
            'description' => fake()->optional()->sentence(),
            'stock_alarm_enabled' => fake()->boolean(),
            'stock_alarm_limit' => fake()->optional()->randomFloat(2, 1, 20),
            'stock_alarm_emails' => fake()->optional()->safeEmail(),
            'is_active' => true,
        ];
    }
}
