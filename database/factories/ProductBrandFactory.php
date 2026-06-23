<?php

namespace Database\Factories;

use App\Models\ProductBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBrand>
 */
class ProductBrandFactory extends Factory
{
    protected $model = ProductBrand::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'is_active' => true,
        ];
    }
}
