<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $professional_schedule_id
 * @property string $starts_at
 * @property string $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'professional_schedule_id',
    'starts_at',
    'ends_at',
])]
class ProfessionalScheduleBreak extends Model
{
    use TenantOwned;

    /**
     * @return BelongsTo<ProfessionalSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ProfessionalSchedule::class, 'professional_schedule_id');
    }
}
