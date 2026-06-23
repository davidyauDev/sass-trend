<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'commission_settlement_id',
    'paid_by',
    'reference',
    'method',
    'amount',
    'paid_at',
    'notes',
])]
class CommissionPayment extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CommissionSettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CommissionSettlement::class, 'commission_settlement_id');
    }
}
