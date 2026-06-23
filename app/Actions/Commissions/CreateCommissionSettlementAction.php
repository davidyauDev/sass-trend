<?php

namespace App\Actions\Commissions;

use App\DTOs\Commissions\CommissionSettlementData;
use App\Models\CommissionSettlement;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionSettlementStatusCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateCommissionSettlementAction
{
    public function __construct(
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, CommissionSettlementData $data): CommissionSettlement
    {
        return DB::transaction(function () use ($actor, $data): CommissionSettlement {
            $settlement = CommissionSettlement::query()->create([
                'branch_id' => $data->branchId,
                'settlement_number' => 'SET-'.Str::upper(Str::random(8)),
                'period_type' => $data->periodType,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt,
                'status' => CommissionSettlementStatusCatalog::DRAFT,
                'notes' => $data->notes,
                'total_commissions' => 0,
                'total_paid' => 0,
            ]);

            $commissions = ProfessionalCommission::query()
                ->when($data->branchId !== null, fn ($query) => $query->where('branch_id', $data->branchId))
                ->whereBetween('generated_at', [$data->startsAt, $data->endsAt])
                ->whereIn('status', ['generated', 'pending_review', 'approved'])
                ->get();

            $total = 0.0;

            foreach ($commissions as $commission) {
                $commission->update([
                    'commission_settlement_id' => $settlement->id,
                    'status' => CommissionSettlementStatusCatalog::DRAFT,
                ]);

                $total += (float) $commission->commission_amount;
            }

            $settlement->update([
                'total_commissions' => $total,
            ]);

            $this->audit->record(
                'settlement.created',
                $actor,
                $data->branchId,
                null,
                null,
                $settlement->id,
                null,
                $settlement->toArray(),
            );

            return $settlement->load('commissions');
        });
    }
}
