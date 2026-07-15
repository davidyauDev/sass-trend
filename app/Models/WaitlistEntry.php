<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\WaitlistEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $client_id
 * @property int $service_id
 * @property int|null $professional_id
 * @property int|null $appointment_id
 * @property Carbon $desired_date
 * @property string $available_from
 * @property string $available_until
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $booked_at
 * @property int|null $created_by
 */
#[Fillable([
    'branch_id',
    'client_id',
    'service_id',
    'professional_id',
    'appointment_id',
    'desired_date',
    'available_from',
    'available_until',
    'status',
    'notes',
    'booked_at',
    'created_by',
])]
class WaitlistEntry extends Model
{
    /** @use HasFactory<WaitlistEntryFactory> */
    use HasFactory, TenantOwned;

    public const string STATUS_WAITING = 'waiting';

    public const string STATUS_BOOKED = 'booked';

    protected function casts(): array
    {
        return [
            'desired_date' => 'date',
            'booked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** @return BelongsTo<User, $this> */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_WAITING && $this->desired_date->isBefore(today());
    }
}
