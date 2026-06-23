<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $barcode
 * @property int|null $brand_id
 * @property int|null $category_id
 * @property int|null $presentation_id
 * @property string $public_sale_price
 * @property string $current_stock
 * @property string $purchase_cost
 * @property string $internal_sale_price
 * @property string $sale_commission
 * @property string $commission_type
 * @property bool $includes_tax
 * @property string|null $description
 * @property bool $stock_alarm_enabled
 * @property string|null $stock_alarm_limit
 * @property string|null $stock_alarm_emails
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'barcode',
    'brand_id',
    'category_id',
    'presentation_id',
    'public_sale_price',
    'current_stock',
    'purchase_cost',
    'internal_sale_price',
    'sale_commission',
    'commission_type',
    'includes_tax',
    'description',
    'stock_alarm_enabled',
    'stock_alarm_limit',
    'stock_alarm_emails',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'public_sale_price' => 'decimal:2',
            'current_stock' => 'decimal:2',
            'purchase_cost' => 'decimal:2',
            'internal_sale_price' => 'decimal:2',
            'sale_commission' => 'decimal:2',
            'stock_alarm_limit' => 'decimal:2',
            'includes_tax' => 'boolean',
            'stock_alarm_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ProductBrand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class);
    }

    /**
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * @return BelongsTo<ProductPresentation, $this>
     */
    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }

    /**
     * @return HasMany<ProductBranchStock, $this>
     */
    public function branchStocks(): HasMany
    {
        return $this->hasMany(ProductBranchStock::class);
    }

    /**
     * @return HasMany<ProductSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(ProductSale::class);
    }

    /**
     * @return HasMany<ProductSaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }

    /**
     * @return HasMany<ProductStockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(ProductStockMovement::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $searchQuery) use ($like): void {
            $searchQuery
                ->where('name', 'like', $like)
                ->orWhere('barcode', 'like', $like)
                ->orWhereHas('brand', fn (Builder $brandQuery): Builder => $brandQuery->where('name', 'like', $like))
                ->orWhereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('name', 'like', $like))
                ->orWhereHas('presentation', fn (Builder $presentationQuery): Builder => $presentationQuery->where('name', 'like', $like));
        });
    }
}
