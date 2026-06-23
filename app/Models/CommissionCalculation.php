<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'professional_commission_id',
    'commission_rule_id',
    'rule_snapshot',
    'formula_snapshot',
    'input_payload',
    'output_payload',
    'calculated_at',
])]
class CommissionCalculation extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'rule_snapshot' => 'array',
            'formula_snapshot' => 'array',
            'input_payload' => 'array',
            'output_payload' => 'array',
            'calculated_at' => 'datetime',
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
