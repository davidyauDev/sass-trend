<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'user_id',
    'sold_at',
    'total',
    'notes',
])]
class ProductSale extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ProductSaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }
}
