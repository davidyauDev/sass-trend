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
    'commission_rule_id',
    'commission_type_id',
    'commission_settlement_id',
    'source_type',
    'source_reference',
    'status',
    'revenue_amount',
    'cost_amount',
    'profit_amount',
    'commission_amount',
    'quantity',
    'currency',
    'approved_by',
    'approved_at',
    'paid_at',
    'generated_at',
    'cancelled_at',
    'metadata',
])]
class ProfessionalCommission extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'revenue_amount' => 'decimal:2',
            'cost_amount' => 'decimal:2',
            'profit_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'quantity' => 'integer',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'generated_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'metadata' => 'array',
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
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<CommissionRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }

    /**
     * @return BelongsTo<CommissionType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CommissionType::class, 'commission_type_id');
    }

    /**
     * @return BelongsTo<CommissionSettlement, $this>
     */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(CommissionSettlement::class, 'commission_settlement_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<CommissionTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CommissionTransaction::class);
    }

    /**
     * @return HasMany<CommissionCalculation, $this>
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(CommissionCalculation::class);
    }

    /**
     * @return HasMany<CommissionApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(CommissionApproval::class);
    }
}
