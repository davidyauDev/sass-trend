<?php

namespace App\Actions\Products;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\Products\ProductStockService;
use Illuminate\Support\Facades\DB;

final class AdjustProductStockAction
{
    public function __construct(
        private readonly ProductStockService $stockService,
    ) {}

    /**
     * @param  array<int|string, mixed>  $stockByBranch
     */
    public function handle(User $actor, Product $product, array $stockByBranch): Product
    {
        return DB::transaction(function () use ($actor, $product, $stockByBranch): Product {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            foreach ($stockByBranch as $branchId => $quantity) {
                $branch = Branch::query()->findOrFail((int) $branchId);
                $this->stockService->adjustBranchStock(
                    $product,
                    $branch,
                    max(0, (float) $quantity),
                    $actor,
                );
            }

            return $product->fresh([
                'brand',
                'category',
                'presentation',
                'branchStocks.branch',
                'stockMovements.branch',
                'stockMovements.user',
            ]);
        });
    }
}
