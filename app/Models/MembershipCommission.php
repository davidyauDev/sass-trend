<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'professional_commission_id',
    'branch_id',
    'user_id',
    'source_label',
    'source_reference',
    'revenue_amount',
    'commission_amount',
    'status',
])]
class MembershipCommission extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'revenue_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<ProfessionalCommission, $this>
     */
    public function professionalCommission(): BelongsTo
    {
        return $this->belongsTo(ProfessionalCommission::class);
    }
}
