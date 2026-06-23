<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'settlement_number',
    'period_type',
    'starts_at',
    'ends_at',
    'status',
    'total_commissions',
    'total_paid',
    'approved_by',
    'approved_at',
    'paid_at',
    'notes',
])]
class CommissionSettlement extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'total_commissions' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
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
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<ProfessionalCommission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(ProfessionalCommission::class);
    }

    /**
     * @return HasMany<CommissionPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }
}
