<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $service_category_id
 * @property string $name
 * @property float $price
 * @property int $duration_minutes
 * @property bool $is_active
 * @property bool $is_bookable_online
 * @property string|null $description
 * @property string|null $image_path
 * @property string|null $online_payment_type
 * @property float|null $deposit_amount
 * @property int|null $deposit_percentage
 * @property bool $is_video_conference
 * @property bool $is_home_service
 * @property bool $has_special_schedule
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'service_category_id',
    'name',
    'price',
    'duration_minutes',
    'is_active',
    'is_bookable_online',
    'description',
    'image_path',
    'online_payment_type',
    'deposit_amount',
    'deposit_percentage',
    'is_video_conference',
    'is_home_service',
    'has_special_schedule',
])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'duration_minutes' => 'integer',
            'deposit_percentage' => 'integer',
            'is_active' => 'boolean',
            'is_bookable_online' => 'boolean',
            'is_video_conference' => 'boolean',
            'is_home_service' => 'boolean',
            'has_special_schedule' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'professional_service')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Professional, $this>
     */
    public function professionalProfiles(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'professional_service_assignments')
            ->withPivot(['sale_commission', 'commission_type'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ServiceSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class)->orderBy('day_of_week');
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        if ($term === '') {
            return $query;
        }

        $like = "%{$term}%";

        return $query->where(function (Builder $searchQuery) use ($like): void {
            $searchQuery
                ->where('name', 'like', $like)
                ->orWhereHas('category', fn (Builder $categoryQuery): Builder => $categoryQuery->where('name', 'like', $like));
        });
    }
}
