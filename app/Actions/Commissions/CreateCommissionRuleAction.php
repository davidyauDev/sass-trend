<?php

namespace App\Actions\Commissions;

use App\Models\CommissionRule;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateCommissionRuleAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): CommissionRule
    {
        return DB::transaction(function () use ($actor, $data): CommissionRule {
            $rule = CommissionRule::query()->create([
                'branch_id' => $data['branch_id'],
                'service_id' => $data['service_id'],
                'service_category_id' => $data['service_category_id'],
                'commission_type_id' => $data['commission_type_id'],
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'priority' => $data['priority'],
                'source_type' => $data['source_type'],
                'calculation_mode' => $data['calculation_mode'],
                'percentage' => $data['percentage'],
                'fixed_amount' => $data['fixed_amount'],
                'min_revenue' => $data['min_revenue'],
                'min_quantity' => $data['min_quantity'],
                'condition_json' => $data['condition_json'],
                'is_active' => $data['is_active'],
                'notes' => $data['notes'],
            ]);

            $this->audit->record(
                'rule.created',
                $actor,
                $rule->branch_id,
                null,
                $rule->id,
                null,
                null,
                $rule->toArray(),
            );

            return $rule->load(['branch', 'service', 'serviceCategory', 'type', 'formulas']);
        });
    }
}
