<?php

namespace App\Services\Products;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\ProductStockMovement;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class ProductStockService
{
    public function currentStock(Product $product): string
    {
        $hasBranchStocks = $product->branchStocks()->exists();

        if (! $hasBranchStocks) {
            return (string) $product->current_stock;
        }

        return number_format((float) $product->branchStocks()->sum('current_stock'), 2, '.', '');
    }

    public function ensureBranchStock(Product $product, Branch $branch): ProductBranchStock
    {
        $stock = $product->branchStocks()
            ->firstOrCreate(
                ['branch_id' => $branch->id],
                ['current_stock' => $product->branchStocks()->exists() ? 0 : $product->current_stock],
            );

        return $stock->refresh();
    }

    public function setBranchStock(
        Product $product,
        Branch $branch,
        float $newStock,
        ?User $actor,
        string $movementType,
        string $reason,
        ?string $comment = null,
        ?ProductSale $sale = null,
        ?ProductSaleItem $saleItem = null,
        ?CarbonImmutable $occurredAt = null,
    ): ProductStockMovement {
        $stock = $this->ensureBranchStock($product, $branch);
        $previousStock = (float) $stock->current_stock;
        $stock->update([
            'current_stock' => $newStock,
        ]);

        $movement = ProductStockMovement::query()->create([
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'product_sale_id' => $sale?->id,
            'product_sale_item_id' => $saleItem?->id,
            'user_id' => $actor?->id,
            'movement_type' => $movementType,
            'previous_stock' => $previousStock,
            'quantity_delta' => round($newStock - $previousStock, 2),
            'new_stock' => $newStock,
            'reason' => $reason,
            'comment' => $comment,
            'occurred_at' => $occurredAt ?? CarbonImmutable::now(),
        ]);

        $this->syncProductTotal($product);

        return $movement->load(['branch', 'user', 'sale', 'saleItem']);
    }

    public function decreaseBranchStock(
        Product $product,
        Branch $branch,
        float $quantity,
        ?User $actor,
        ?ProductSale $sale = null,
        ?ProductSaleItem $saleItem = null,
    ): ProductStockMovement {
        $stock = $this->ensureBranchStock($product, $branch);
        $previousStock = (float) $stock->current_stock;

        if ($previousStock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'No hay stock suficiente en el local seleccionado.',
            ]);
        }

        $newStock = round($previousStock - $quantity, 2);

        return $this->setBranchStock(
            $product,
            $branch,
            $newStock,
            $actor,
            'sale',
            'Venta de producto.',
            null,
            $sale,
            $saleItem,
        );
    }

    public function adjustBranchStock(
        Product $product,
        Branch $branch,
        float $newStock,
        ?User $actor,
        ?string $comment = null,
    ): ProductStockMovement {
        return $this->setBranchStock(
            $product,
            $branch,
            $newStock,
            $actor,
            'adjustment',
            'Ajuste manual de stock.',
            $comment,
        );
    }

    public function syncProductTotal(Product $product): Product
    {
        if (! $product->branchStocks()->exists()) {
            return $product;
        }

        $product->forceFill([
            'current_stock' => $product->branchStocks()->sum('current_stock'),
        ])->saveQuietly();

        return $product;
    }
}
