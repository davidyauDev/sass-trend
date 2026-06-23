<?php

namespace App\Actions\Commissions;

use App\Models\CommissionTransaction;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionStatusCatalog;
use Carbon\CarbonImmutable;

final class ReverseCommissionAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, ProfessionalCommission $commission, string $reason): ProfessionalCommission
    {
        $previous = $commission->toArray();

        $commission->update([
            'status' => CommissionStatusCatalog::CANCELLED,
            'cancelled_at' => CarbonImmutable::now(),
        ]);

        CommissionTransaction::query()->create([
            'professional_commission_id' => $commission->id,
            'transaction_type' => 'reversed',
            'amount' => -1 * (float) $commission->commission_amount,
            'reference' => $reason,
            'transaction_at' => CarbonImmutable::now(),
            'metadata' => ['reason' => $reason],
        ]);

        $this->audit->record(
            'commission.reversed',
            $actor,
            $commission->branch_id,
            $commission->id,
            $commission->commission_rule_id,
            null,
            $previous,
            $commission->fresh()->toArray(),
        );

        return $commission->fresh(['branch', 'professional', 'rule', 'type', 'transactions']);
    }
}
