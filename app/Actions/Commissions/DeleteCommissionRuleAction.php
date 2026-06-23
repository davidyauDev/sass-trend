<?php

namespace App\Actions\Commissions;

use App\Models\CommissionRule;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;

final class DeleteCommissionRuleAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, CommissionRule $rule): void
    {
        $this->audit->record(
            'rule.deleted',
            $actor,
            $rule->branch_id,
            null,
            $rule->id,
            null,
            $rule->toArray(),
            null,
        );

        $rule->delete();
    }
}
