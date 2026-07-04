<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;

final class SaleListingQuery
{
    /**
     * @param  array{search?:string,period?:string,client?:string|int|null,status?:string,payment?:string,branch?:string|int|null}  $filters
     * @return Builder<Sale>
     */
    public function handle(array $filters = []): Builder
    {
        $query = Sale::query()
            ->with(['client', 'branch', 'payments', 'user']);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->search($search);
        }

        $period = (string) ($filters['period'] ?? '7');

        if ($period !== 'all') {
            $days = max(1, (int) $period);
            $query->where('sold_at', '>=', now()->subDays($days)->startOfDay());
        }

        $clientId = $filters['client'] ?? '';

        if ($clientId !== '') {
            $query->where('client_id', (int) $clientId);
        }

        $paymentMethod = (string) ($filters['payment'] ?? '');

        if ($paymentMethod !== '') {
            $query->whereHas('payments', fn (Builder $paymentQuery): Builder => $paymentQuery->where('method', $paymentMethod));
        }

        $branchId = $filters['branch'] ?? '';

        if ($branchId !== '') {
            $query->where('branch_id', (int) $branchId);
        }

        $status = (string) ($filters['status'] ?? '');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }
}
