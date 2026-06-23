<?php

namespace App\Actions\Commissions;

use App\Models\CommissionRule;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use Illuminate\Support\Facades\DB;

final class UpdateCommissionRuleAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, CommissionRule $rule, array $data): CommissionRule
    {
        return DB::transaction(function () use ($actor, $rule, $data): CommissionRule {
            $previous = $rule->toArray();

            $rule->update($data);

            $this->audit->record(
                'rule.updated',
                $actor,
                $rule->branch_id,
                null,
                $rule->id,
                null,
                $previous,
                $rule->fresh()->toArray(),
            );

            return $rule->fresh(['branch', 'service', 'serviceCategory', 'type', 'formulas']);
        });
    }
}
