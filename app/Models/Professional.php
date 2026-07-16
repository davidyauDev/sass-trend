<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use App\Support\TenantAsset;
use Database\Factories\ProfessionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $public_name
 * @property string|null $email
 * @property bool $accepts_online_bookings
 * @property bool $has_system_access
 * @property string|null $bio
 * @property string|null $photo_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'user_id',
    'public_name',
    'email',
    'accepts_online_bookings',
    'has_system_access',
    'bio',
    'photo_path',
    'sale_commission',
    'commission_type',
    'is_active',
])]
class Professional extends Model
{
    /** @use HasFactory<ProfessionalFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'accepts_online_bookings' => 'boolean',
            'has_system_access' => 'boolean',
            'sale_commission' => 'decimal:2',
            'commission_type' => 'string',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_professional')->withTimestamps();
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'professional_service_assignments')
            ->withPivot(['sale_commission', 'commission_type'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<ProfessionalSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ProfessionalSchedule::class)->orderBy('day_of_week');
    }

    /**
     * @return BelongsToMany<ProfessionalGroup, $this>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionalGroup::class, 'professional_group_members')->withTimestamps();
    }

    public function displayName(): string
    {
        return $this->public_name;
    }

    public function initials(): string
    {
        return Str::of($this->displayName())
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word): string => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function photoUrl(?string $tenantSlug = null): ?string
    {
        return $this->photo_path !== null ? TenantAsset::url($this->photo_path, $tenantSlug) : null;
    }

    public function hasLinkedUser(): bool
    {
        return $this->user_id !== null;
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

        return $query->where(function (Builder $professionalQuery) use ($like): void {
            $professionalQuery
                ->where('public_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('services', fn (Builder $serviceQuery): Builder => $serviceQuery->where('name', 'like', $like))
                ->orWhereHas('groups', fn (Builder $groupQuery): Builder => $groupQuery->where('name', 'like', $like));
        });
    }
}
