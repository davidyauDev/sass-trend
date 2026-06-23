<?php

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Products\ProductStockService;
use App\Services\Sales\SaleManagementGuard;
use App\Services\Sales\SaleStatusCatalog;
use Illuminate\Support\Facades\DB;

final class DeleteSaleAction
{
    public function __construct(
        private readonly SaleManagementGuard $guard,
        private readonly ProductStockService $stockService,
    ) {}

    public function handle(User $actor, Sale $sale): void
    {
        $this->guard->ensureCanDelete($actor);

        DB::transaction(function () use ($actor, $sale): void {
            $sale->loadMissing(['branch', 'items.product']);

            if ($sale->status !== SaleStatusCatalog::DRAFT) {
                foreach ($sale->items as $item) {
                    if ($item->product_id === null || ! $item->product instanceof Product) {
                        continue;
                    }

                    $stock = $this->stockService->ensureBranchStock($item->product, $sale->branch);
                    $currentStock = (float) $stock->current_stock;

                    $this->stockService->setBranchStock(
                        $item->product,
                        $sale->branch,
                        round($currentStock + (float) $item->quantity, 2),
                        $actor,
                        'adjustment',
                        'Anulación de venta.',
                        'Venta eliminada desde el módulo de ventas.',
                    );
                }
            }

            $sale->delete();
        });
    }
}
