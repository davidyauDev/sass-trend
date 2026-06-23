<?php

namespace App\Actions\Commissions;

use App\Models\CommissionSettlement;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionSettlementStatusCatalog;
use Carbon\CarbonImmutable;

final class ApproveCommissionSettlementAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, CommissionSettlement $settlement): CommissionSettlement
    {
        $previous = $settlement->toArray();

        $settlement->update([
            'status' => CommissionSettlementStatusCatalog::APPROVED,
            'approved_by' => $actor->id,
            'approved_at' => CarbonImmutable::now(),
        ]);

        $this->audit->record(
            'settlement.approved',
            $actor,
            $settlement->branch_id,
            null,
            null,
            $settlement->id,
            $previous,
            $settlement->fresh()->toArray(),
        );

        return $settlement->fresh(['branch', 'approver', 'commissions']);
    }
}
