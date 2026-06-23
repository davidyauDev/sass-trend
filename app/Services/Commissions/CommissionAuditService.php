<?php

namespace App\Services\Commissions;

use App\Models\CommissionAuditLog;
use App\Models\User;

final class CommissionAuditService
{
    /**
     * @param  array<string, mixed>|null  $previous
     * @param  array<string, mixed>|null  $new
     */
    public function record(
        string $action,
        ?User $actor = null,
        ?int $branchId = null,
        ?int $commissionId = null,
        ?int $ruleId = null,
        ?int $settlementId = null,
        ?array $previous = null,
        ?array $new = null,
    ): CommissionAuditLog {
        return CommissionAuditLog::query()->create([
            'branch_id' => $branchId,
            'professional_commission_id' => $commissionId,
            'commission_rule_id' => $ruleId,
            'commission_settlement_id' => $settlementId,
            'user_id' => $actor?->id,
            'action' => $action,
            'previous_value' => $previous,
            'new_value' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
