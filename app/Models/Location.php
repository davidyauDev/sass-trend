<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\LocationFactory;
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
 * @property string $name
 * @property string|null $site_name
 * @property string|null $tagline
 * @property string $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $timezone
 * @property int|null $branch_id
 * @property bool $accepts_online_bookings
 * @property string|null $secondary_phone
 * @property string|null $description
 * @property string|null $image_path
 * @property string|null $logo_path
 * @property string|null $hero_image_path
 * @property string $primary_color
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $whatsapp_phone
 * @property string|null $instagram_url
 * @property string|null $facebook_url
 * @property string|null $tiktok_url
 * @property string $booking_button_label
 * @property string|null $booking_intro
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'site_name',
    'tagline',
    'address',
    'phone',
    'email',
    'timezone',
    'branch_id',
    'accepts_online_bookings',
    'secondary_phone',
    'description',
    'image_path',
    'logo_path',
    'hero_image_path',
    'primary_color',
    'contact_phone',
    'contact_email',
    'whatsapp_phone',
    'instagram_url',
    'facebook_url',
    'tiktok_url',
    'booking_button_label',
    'booking_intro',
    'is_active',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'accepts_online_bookings' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function defaults(): array
    {
        return [
            'site_name' => null,
            'tagline' => null,
            'description' => null,
            'image_path' => null,
            'logo_path' => null,
            'hero_image_path' => null,
            'primary_color' => '#4b3626',
            'contact_phone' => null,
            'contact_email' => null,
            'whatsapp_phone' => null,
            'instagram_url' => null,
            'facebook_url' => null,
            'tiktok_url' => null,
            'booking_button_label' => 'Reservar ahora',
            'booking_intro' => null,
        ];
    }

    /**
     * @return HasMany<LocationSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LocationSchedule::class)->orderBy('day_of_week');
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Professional, $this>
     */
    public function professionalProfiles(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'location_professional')->withTimestamps();
    }

    /**
     * @param  Builder<Location>  $query
     * @return Builder<Location>
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
                ->orWhere('address', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
