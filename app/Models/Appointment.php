<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $reference_code
 * @property int $branch_id
 * @property int $client_id
 * @property int $service_id
 * @property int|null $resource_id
 * @property int|null $professional_id
 * @property int $appointment_status_id
 * @property string $title
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $duration_minutes
 * @property string $timezone
 * @property float $price
 * @property string $currency
 * @property string|null $notes
 * @property string|null $cancellation_reason
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $no_show_at
 * @property int|null $rescheduled_from_id
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'reference_code',
    'branch_id',
    'client_id',
    'service_id',
    'resource_id',
    'professional_id',
    'appointment_status_id',
    'title',
    'starts_at',
    'ends_at',
    'duration_minutes',
    'timezone',
    'price',
    'currency',
    'notes',
    'cancellation_reason',
    'completed_at',
    'cancelled_at',
    'no_show_at',
    'rescheduled_from_id',
    'created_by',
    'updated_by',
])]
class Appointment extends Model
{
    use SoftDeletes, TenantOwned;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'no_show_at' => 'datetime',
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<ResourceModel, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceModel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /**
     * @return BelongsTo<AppointmentStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(AppointmentStatus::class, 'appointment_status_id');
    }

    /**
     * @return BelongsTo<Appointment, $this>
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return HasMany<AppointmentNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class);
    }

    /**
     * @return HasMany<AppointmentPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(AppointmentPayment::class);
    }

    /**
     * @return HasMany<AppointmentHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(AppointmentHistory::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(function (Builder $appointmentQuery) use ($like): void {
            $appointmentQuery
                ->where('reference_code', 'like', $like)
                ->orWhere('title', 'like', $like)
                ->orWhereHas('client', fn (Builder $clientQuery): Builder => $clientQuery->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                ->orWhereHas('service', fn (Builder $serviceQuery): Builder => $serviceQuery->where('name', 'like', $like))
                ->orWhereHas('professional', fn (Builder $professionalQuery): Builder => $professionalQuery->where('name', 'like', $like));
        });
    }
}
