<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int|null $client_id
 * @property int|null $user_id
 * @property string|null $sale_number
 * @property string|null $ticket_number
 * @property Carbon $sold_at
 * @property string $status
 * @property string $subtotal
 * @property string $discount_total
 * @property string $total
 * @property string $paid_total
 * @property string $change_total
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'branch_id',
    'client_id',
    'user_id',
    'sale_number',
    'ticket_number',
    'sold_at',
    'status',
    'subtotal',
    'discount_total',
    'total',
    'paid_total',
    'change_total',
    'notes',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory, SoftDeletes, TenantOwned;

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'change_total' => 'decimal:2',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * @return HasMany<SalePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(function (Builder $saleQuery) use ($like): void {
            $saleQuery
                ->where('sale_number', 'like', $like)
                ->orWhere('ticket_number', 'like', $like)
                ->orWhereHas('client', fn (Builder $clientQuery): Builder => $clientQuery
                    ->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->orWhereHas('branch', fn (Builder $branchQuery): Builder => $branchQuery->where('name', 'like', $like));
        });
    }
}
