<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property int|null $user_id
 * @property string $note
 * @property bool $is_internal
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'appointment_id',
    'user_id',
    'note',
    'is_internal',
])]
class AppointmentNote extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
