<?php

namespace Database\Factories;

use App\Models\ProductPresentation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPresentation>
 */
class ProductPresentationFactory extends Factory
{
    protected $model = ProductPresentation::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Caja',
                'Frasco',
                'Unidad',
                'Sachet',
                'Litro',
            ]),
            'is_active' => true,
        ];
    }
}
