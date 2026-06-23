<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'professional_commission_id',
    'reviewer_id',
    'status',
    'notes',
    'reviewed_at',
])]
class CommissionApproval extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
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
