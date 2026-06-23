<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property int $day_of_week
 * @property bool $is_open
 * @property string|null $opens_at
 * @property string|null $closes_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'day_of_week',
    'is_open',
    'opens_at',
    'closes_at',
])]
class LocationSchedule extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
