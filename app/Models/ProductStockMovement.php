<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'branch_id',
    'product_sale_id',
    'product_sale_item_id',
    'user_id',
    'movement_type',
    'previous_stock',
    'quantity_delta',
    'new_stock',
    'reason',
    'comment',
    'occurred_at',
])]
class ProductStockMovement extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'previous_stock' => 'decimal:2',
            'quantity_delta' => 'decimal:2',
            'new_stock' => 'decimal:2',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<ProductSale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(ProductSale::class, 'product_sale_id');
    }

    /**
     * @return BelongsTo<ProductSaleItem, $this>
     */
    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(ProductSaleItem::class, 'product_sale_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
