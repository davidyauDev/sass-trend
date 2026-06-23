<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Services\Commissions\CommissionSourceCatalog;
use App\Services\Commissions\CommissionStatusCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalCommission>
 */
class ProfessionalCommissionFactory extends Factory
{
    protected $model = ProfessionalCommission::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            'commission_rule_id' => null,
            'commission_type_id' => null,
            'commission_settlement_id' => null,
            'source_type' => CommissionSourceCatalog::APPOINTMENT,
            'source_reference' => fake()->uuid(),
            'status' => CommissionStatusCatalog::GENERATED,
            'revenue_amount' => fake()->randomFloat(2, 50, 1000),
            'cost_amount' => null,
            'profit_amount' => null,
            'commission_amount' => fake()->randomFloat(2, 5, 150),
            'quantity' => 1,
            'currency' => 'PEN',
            'approved_by' => null,
            'approved_at' => null,
            'paid_at' => null,
            'generated_at' => now(),
            'cancelled_at' => null,
            'metadata' => [],
        ];
    }
}
