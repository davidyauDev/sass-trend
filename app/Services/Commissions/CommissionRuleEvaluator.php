<?php

namespace App\Services\Commissions;

use App\DTOs\Commissions\CommissionGenerationData;
use App\Models\CommissionRule;

final class CommissionRuleEvaluator
{
    /**
     * @param  iterable<CommissionRule>  $rules
     */
    public function resolve(iterable $rules, CommissionGenerationData $data): ?CommissionRule
    {
        foreach ($rules as $rule) {
            if ($this->matches($rule, $data)) {
                return $rule;
            }
        }

        return null;
    }

    public function calculateAmount(CommissionRule $rule, CommissionGenerationData $data): float
    {
        $base = $data->revenueAmount;
        $profit = max(0, $data->revenueAmount - (float) ($data->costAmount ?? 0));

        return match ($rule->calculation_mode) {
            'fixed' => (float) $rule->fixed_amount,
            'profit' => $profit * ((float) $rule->percentage / 100),
            'quantity' => (float) $rule->fixed_amount * $data->quantity,
            default => $base * ((float) $rule->percentage / 100),
        };
    }

    private function matches(CommissionRule $rule, CommissionGenerationData $data): bool
    {
        if (! $rule->is_active) {
            return false;
        }

        if ($rule->branch_id !== null && $rule->branch_id !== $data->branchId) {
            return false;
        }

        if ($rule->source_type !== null && $rule->source_type !== $data->sourceType) {
            return false;
        }

        if ($rule->min_revenue !== null && $data->revenueAmount < (float) $rule->min_revenue) {
            return false;
        }

        if ($rule->min_quantity !== null && $data->quantity < $rule->min_quantity) {
            return false;
        }

        if ($rule->service_id !== null && ($data->metadata['service_id'] ?? null) !== $rule->service_id) {
            return false;
        }

        if ($rule->service_category_id !== null && ($data->metadata['service_category_id'] ?? null) !== $rule->service_category_id) {
            return false;
        }

        return true;
    }
}
