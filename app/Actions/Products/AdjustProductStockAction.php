<?php

namespace App\Actions\Products;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\Products\ProductStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function handleSingleBranchAdjustment(
        User $actor,
        Product $product,
        Branch $branch,
        float $quantity,
        string $direction,
        ?string $comment = null,
    ): Product {
        return DB::transaction(function () use ($actor, $product, $branch, $quantity, $direction, $comment): Product {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $branch = Branch::query()->findOrFail($branch->id);
            $stock = $this->stockService->ensureBranchStock($product, $branch);
            $currentStock = (float) $stock->current_stock;

            $newStock = $direction === 'increase'
                ? round($currentStock + $quantity, 2)
                : round($currentStock - $quantity, 2);

            if ($direction === 'decrease' && $newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'No puedes reducir más stock del disponible en el local seleccionado.',
                ]);
            }

            $reason = $direction === 'increase'
                ? 'Aumento manual de stock por local.'
                : 'Reducción manual de stock por local.';

            $this->stockService->setBranchStock(
                $product,
                $branch,
                $newStock,
                $actor,
                'adjustment',
                $reason,
                $comment,
            );

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
