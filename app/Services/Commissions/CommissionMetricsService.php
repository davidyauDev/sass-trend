<?php

namespace App\Services\Commissions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CommissionMetricsService
{
    /**
     * @return array{total_commissions: float, pending_commissions: float, approved_commissions: float, paid_commissions: float, revenue_generated: float}
     */
    public function dashboardMetrics(?int $branchId = null): array
    {
        $query = DB::table('professional_commissions');

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return [
            'total_commissions' => (float) (clone $query)->sum('commission_amount'),
            'pending_commissions' => (float) (clone $query)->where('status', CommissionStatusCatalog::PENDING_REVIEW)->sum('commission_amount'),
            'approved_commissions' => (float) (clone $query)->where('status', CommissionStatusCatalog::APPROVED)->sum('commission_amount'),
            'paid_commissions' => (float) (clone $query)->where('status', CommissionStatusCatalog::PAID)->sum('commission_amount'),
            'revenue_generated' => (float) (clone $query)->sum('revenue_amount'),
        ];
    }

    /**
     * @return Collection<int, array{user_id:int,name:string,revenue:float,commissions:float,completed:int}>
     */
    public function topPerformers(?int $branchId = null, int $limit = 5): Collection
    {
        return DB::table('professional_commissions')
            ->join('users', 'users.id', '=', 'professional_commissions.user_id')
            ->when($branchId !== null, fn ($query) => $query->where('professional_commissions.branch_id', $branchId))
            ->selectRaw('professional_commissions.user_id as user_id, users.name as name, SUM(professional_commissions.revenue_amount) as revenue, SUM(professional_commissions.commission_amount) as commissions, COUNT(*) as completed')
            ->groupBy('professional_commissions.user_id', 'users.name')
            ->orderByDesc('commissions')
            ->limit($limit)
            ->get()
            ->map(function (object $commission): array {
                return [
                    'user_id' => (int) $commission->user_id,
                    'name' => (string) $commission->name,
                    'revenue' => (float) $commission->revenue,
                    'commissions' => (float) $commission->commissions,
                    'completed' => (int) $commission->completed,
                ];
            });
    }
}
