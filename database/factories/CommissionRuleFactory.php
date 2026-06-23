<?php

namespace Database\Factories;

use App\Models\CommissionRule;
use App\Models\CommissionType;
use App\Services\Commissions\CommissionSourceCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    public function definition(): array
    {
        return [
            'branch_id' => null,
            'service_id' => null,
            'service_category_id' => null,
            'commission_type_id' => CommissionType::factory(),
            'name' => fake()->sentence(3),
            'slug' => fake()->slug(),
            'priority' => fake()->numberBetween(1, 100),
            'source_type' => fake()->randomElement(CommissionSourceCatalog::values()),
            'calculation_mode' => 'percentage',
            'percentage' => fake()->randomFloat(2, 1, 20),
            'fixed_amount' => null,
            'min_revenue' => null,
            'min_quantity' => null,
            'condition_json' => null,
            'is_active' => true,
            'notes' => fake()->sentence(),
        ];
    }
}
