<?php

namespace App\Actions\Commissions;

use App\Models\CommissionPayment;
use App\Models\CommissionSettlement;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionSettlementStatusCatalog;
use Carbon\CarbonImmutable;

final class MarkCommissionSettlementPaidAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, CommissionSettlement $settlement, float $amount, ?string $reference = null, ?string $method = null): CommissionSettlement
    {
        $previous = $settlement->toArray();

        CommissionPayment::query()->create([
            'commission_settlement_id' => $settlement->id,
            'paid_by' => $actor->id,
            'reference' => $reference,
            'method' => $method,
            'amount' => $amount,
            'paid_at' => CarbonImmutable::now(),
        ]);

        $settlement->update([
            'status' => CommissionSettlementStatusCatalog::PAID,
            'paid_at' => CarbonImmutable::now(),
            'total_paid' => $amount,
        ]);

        $this->audit->record(
            'settlement.paid',
            $actor,
            $settlement->branch_id,
            null,
            null,
            $settlement->id,
            $previous,
            $settlement->fresh()->toArray(),
        );

        return $settlement->fresh(['branch', 'approver', 'payments', 'commissions']);
    }
}
