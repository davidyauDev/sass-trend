<?php

namespace App\Actions\Commissions;

use App\Models\CommissionApproval;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionStatusCatalog;
use Carbon\CarbonImmutable;

final class RejectCommissionAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, ProfessionalCommission $commission, ?string $notes = null): ProfessionalCommission
    {
        $previous = $commission->toArray();

        CommissionApproval::query()->create([
            'professional_commission_id' => $commission->id,
            'reviewer_id' => $actor->id,
            'status' => CommissionStatusCatalog::REJECTED,
            'notes' => $notes,
            'reviewed_at' => CarbonImmutable::now(),
        ]);

        $commission->update([
            'status' => CommissionStatusCatalog::REJECTED,
        ]);

        $this->audit->record(
            'commission.rejected',
            $actor,
            $commission->branch_id,
            $commission->id,
            $commission->commission_rule_id,
            null,
            $previous,
            $commission->fresh()->toArray(),
        );

        return $commission->fresh(['branch', 'professional', 'rule', 'type']);
    }
}
