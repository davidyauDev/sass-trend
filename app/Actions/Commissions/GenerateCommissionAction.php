<?php

namespace App\Actions\Commissions;

use App\DTOs\Commissions\CommissionGenerationData;
use App\Models\CommissionCalculation;
use App\Models\CommissionRule;
use App\Models\CommissionTransaction;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Services\Commissions\CommissionAuditService;
use App\Services\Commissions\CommissionRuleEvaluator;
use App\Services\Commissions\CommissionStatusCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class GenerateCommissionAction
{
    public function __construct(
        private readonly CommissionRuleEvaluator $evaluator,
        private readonly CommissionAuditService $audit,
    ) {}

    public function handle(User $actor, CommissionGenerationData $data): ProfessionalCommission
    {
        return DB::transaction(function () use ($actor, $data): ProfessionalCommission {
            $rules = CommissionRule::query()
                ->with('formulas')
                ->where('is_active', true)
                ->when($data->branchId !== 0, fn ($query) => $query->where(fn ($subQuery) => $subQuery->whereNull('branch_id')->orWhere('branch_id', $data->branchId)))
                ->when($data->commissionRuleId !== null, fn ($query) => $query->whereKey($data->commissionRuleId))
                ->orderBy('priority')
                ->get();

            $rule = $this->evaluator->resolve($rules, $data);
            $amount = $rule !== null ? $this->evaluator->calculateAmount($rule, $data) : 0.0;
            $commission = ProfessionalCommission::query()->updateOrCreate(
                [
                    'source_type' => $data->sourceType,
                    'source_reference' => $data->sourceReference,
                    'user_id' => $data->userId,
                ],
                [
                    'branch_id' => $data->branchId,
                    'commission_rule_id' => $rule?->id,
                    'commission_type_id' => $rule->commission_type_id ?? $data->commissionTypeId,
                    'status' => CommissionStatusCatalog::GENERATED,
                    'revenue_amount' => $data->revenueAmount,
                    'cost_amount' => $data->costAmount,
                    'profit_amount' => $data->costAmount !== null ? max(0, $data->revenueAmount - $data->costAmount) : null,
                    'commission_amount' => $amount,
                    'quantity' => $data->quantity,
                    'generated_at' => CarbonImmutable::now(),
                    'metadata' => $data->metadata + ['description' => $data->description],
                ],
            );

            CommissionCalculation::query()->create([
                'professional_commission_id' => $commission->id,
                'commission_rule_id' => $rule?->id,
                'rule_snapshot' => $rule?->toArray(),
                'formula_snapshot' => $rule?->formulas->toArray(),
                'input_payload' => [
                    'source_type' => $data->sourceType,
                    'source_reference' => $data->sourceReference,
                    'revenue_amount' => $data->revenueAmount,
                    'quantity' => $data->quantity,
                ],
                'output_payload' => [
                    'commission_amount' => $amount,
                    'status' => $commission->status,
                ],
                'calculated_at' => CarbonImmutable::now(),
            ]);

            CommissionTransaction::query()->create([
                'professional_commission_id' => $commission->id,
                'transaction_type' => 'generated',
                'amount' => $amount,
                'reference' => $commission->source_reference,
                'transaction_at' => CarbonImmutable::now(),
                'metadata' => $data->metadata,
            ]);

            $this->audit->record(
                'commission.generated',
                $actor,
                $data->branchId,
                $commission->id,
                $rule?->id,
                null,
                null,
                $commission->toArray(),
            );

            return $commission->load(['branch', 'professional', 'rule', 'type', 'transactions', 'calculations']);
        });
    }
}
