<?php

namespace App\Repositories\Commissions;

use App\Models\CommissionAuditLog;
use App\Models\CommissionRule;
use App\Models\CommissionSettlement;
use App\Models\ProfessionalCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class CommissionRepository
{
    /**
     * @param  array{search:string,branch_id:int|null,user_id:int|null,status:string,source_type:string,date_from:string,date_to:string}  $filters
     * @return LengthAwarePaginator<int, ProfessionalCommission>
     */
    public function commissions(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return ProfessionalCommission::query()
            ->with(['branch', 'professional', 'rule', 'type', 'settlement', 'approver'])
            ->when($filters['search'] !== '', fn (Builder $query): Builder => $query->where(function (Builder $searchQuery) use ($filters): void {
                $searchQuery
                    ->where('source_reference', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('professional', fn (Builder $professionalQuery): Builder => $professionalQuery->where('name', 'like', '%'.$filters['search'].'%'))
                    ->orWhereHas('branch', fn (Builder $branchQuery): Builder => $branchQuery->where('name', 'like', '%'.$filters['search'].'%'));
            }))
            ->when($filters['branch_id'] !== null, fn (Builder $query): Builder => $query->where('branch_id', $filters['branch_id']))
            ->when($filters['user_id'] !== null, fn (Builder $query): Builder => $query->where('user_id', $filters['user_id']))
            ->when($filters['status'] !== '', fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->when($filters['source_type'] !== '', fn (Builder $query): Builder => $query->where('source_type', $filters['source_type']))
            ->when($filters['date_from'] !== '', fn (Builder $query): Builder => $query->whereDate('generated_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query): Builder => $query->whereDate('generated_at', '<=', $filters['date_to']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, CommissionRule>
     */
    public function rules(): Collection
    {
        return CommissionRule::query()
            ->with(['branch', 'service', 'serviceCategory', 'type', 'formulas'])
            ->orderBy('priority')
            ->get();
    }

    /**
     * @param  array{branch_id:int|null,status:string}  $filters
     * @return LengthAwarePaginator<int, CommissionSettlement>
     */
    public function settlements(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return CommissionSettlement::query()
            ->with(['branch', 'approver', 'commissions'])
            ->when($filters['branch_id'] !== null, fn (Builder $query): Builder => $query->where('branch_id', $filters['branch_id']))
            ->when($filters['status'] !== '', fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array{branch_id:int|null}  $filters
     * @return LengthAwarePaginator<int, CommissionAuditLog>
     */
    public function auditLogs(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return CommissionAuditLog::query()
            ->with(['user'])
            ->when($filters['branch_id'] !== null, fn (Builder $query): Builder => $query->where('branch_id', $filters['branch_id']))
            ->latest()
            ->paginate($perPage);
    }
}
