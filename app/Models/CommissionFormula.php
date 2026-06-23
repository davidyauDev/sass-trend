<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'commission_rule_id',
    'label',
    'threshold_operator',
    'threshold_value',
    'bonus_amount',
    'bonus_percentage',
    'condition_json',
    'is_active',
])]
class CommissionFormula extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'bonus_percentage' => 'decimal:2',
            'condition_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<CommissionRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class, 'commission_rule_id');
    }
}
