<?php

namespace App\Repositories\Commissions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CommissionReportRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array{label:string,total:float}>
     */
    public function bestSellingServices(array $filters, int $limit = 5): Collection
    {
        return DB::table('professional_commissions')
            ->where('source_type', 'appointment')
            ->selectRaw('source_reference as label, SUM(revenue_amount) as total')
            ->groupBy('source_reference')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn (object $service): array => [
                'label' => 'Service #'.$service->label,
                'total' => (float) $service->total,
            ]);
    }
}
