<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'branch_id',
    'service_id',
    'service_category_id',
    'commission_type_id',
    'name',
    'slug',
    'priority',
    'source_type',
    'calculation_mode',
    'percentage',
    'fixed_amount',
    'min_revenue',
    'min_quantity',
    'condition_json',
    'is_active',
    'notes',
])]
class CommissionRule extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'percentage' => 'decimal:2',
            'fixed_amount' => 'decimal:2',
            'min_revenue' => 'decimal:2',
            'min_quantity' => 'integer',
            'condition_json' => 'array',
            'is_active' => 'boolean',
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
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return BelongsTo<CommissionType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(CommissionType::class, 'commission_type_id');
    }

    /**
     * @return HasMany<CommissionFormula, $this>
     */
    public function formulas(): HasMany
    {
        return $this->hasMany(CommissionFormula::class);
    }

    /**
     * @return HasMany<ProfessionalCommission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(ProfessionalCommission::class);
    }
}
