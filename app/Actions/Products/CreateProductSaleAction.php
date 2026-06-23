<?php

namespace App\Actions\Products;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\User;
use App\Services\Products\ProductStockService;
use Illuminate\Support\Facades\DB;

final class CreateProductSaleAction
{
    public function __construct(
        private readonly ProductStockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): ProductSale
    {
        return DB::transaction(function () use ($actor, $data): ProductSale {
            $product = Product::query()->lockForUpdate()->findOrFail((int) $data['product_id']);
            $branch = Branch::query()->lockForUpdate()->findOrFail((int) $data['branch_id']);

            $quantity = (float) $data['quantity'];
            $unitPrice = (float) ($data['unit_price'] ?? $product->public_sale_price);

            $sale = ProductSale::query()->create([
                'branch_id' => $branch->id,
                'user_id' => $actor->id,
                'sold_at' => now(),
                'total' => round($quantity * $unitPrice, 2),
                'notes' => isset($data['notes']) && $data['notes'] !== '' ? $data['notes'] : null,
            ]);

            $item = ProductSaleItem::query()->create([
                'product_sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($quantity * $unitPrice, 2),
            ]);

            $this->stockService->decreaseBranchStock($product, $branch, $quantity, $actor, $sale, $item);

            return $sale->load(['branch', 'user', 'items.product.presentation', 'items.product.brand']);
        });
    }
}
