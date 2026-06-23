<?php

namespace Database\Factories;

use App\Models\CommissionType;
use App\Services\Commissions\CommissionTypeCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionType>
 */
class CommissionTypeFactory extends Factory
{
    protected $model = CommissionType::class;

    public function definition(): array
    {
        $definition = fake()->randomElement(CommissionTypeCatalog::definitions());

        return [
            'name' => $definition['name'],
            'slug' => $definition['slug'],
            'calculation_basis' => $definition['slug'],
            'is_active' => true,
        ];
    }
}
