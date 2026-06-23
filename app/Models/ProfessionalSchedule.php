<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $professional_id
 * @property int $day_of_week
 * @property bool $is_working
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'professional_id',
    'day_of_week',
    'is_working',
    'starts_at',
    'ends_at',
])]
class ProfessionalSchedule extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_working' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Professional, $this>
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /**
     * @return HasMany<ProfessionalScheduleBreak, $this>
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(ProfessionalScheduleBreak::class)->orderBy('starts_at');
    }
}
