<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'professional_commission_id',
    'commission_rule_id',
    'commission_settlement_id',
    'user_id',
    'action',
    'previous_value',
    'new_value',
    'ip_address',
    'user_agent',
])]
class CommissionAuditLog extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'previous_value' => 'array',
            'new_value' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
