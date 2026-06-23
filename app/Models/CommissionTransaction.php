<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'professional_commission_id',
    'transaction_type',
    'amount',
    'reference',
    'transaction_at',
    'metadata',
])]
class CommissionTransaction extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ProfessionalCommission, $this>
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(ProfessionalCommission::class, 'professional_commission_id');
    }
}
