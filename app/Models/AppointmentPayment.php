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
 * @property float $amount
 * @property string $method
 * @property string $status
 * @property string $reference
 * @property CarbonImmutable|null $paid_at
 * @property string|null $notes
 */
#[Fillable([
    'appointment_id',
    'amount',
    'method',
    'status',
    'reference',
    'paid_at',
    'notes',
])]
class AppointmentPayment extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
