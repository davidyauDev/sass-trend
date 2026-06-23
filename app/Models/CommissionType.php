<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\CommissionTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'calculation_basis',
    'is_active',
])]
class CommissionType extends Model
{
    /** @use HasFactory<CommissionTypeFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CommissionRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }
}
