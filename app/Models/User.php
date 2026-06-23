<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\TenantOwned;
use App\Services\Users\UserRoleCatalog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $photo_path
 * @property string|null $tenant_id
 * @property int|null $role_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_active
 * @property bool $is_primary_admin
 * @property Carbon|null $invited_at
 * @property Carbon|null $invitation_accepted_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'name',
    'first_name',
    'last_name',
    'email',
    'phone',
    'photo_path',
    'role_id',
    'password',
    'is_active',
    'is_primary_admin',
    'invited_at',
    'invitation_accepted_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TenantOwned, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_primary_admin' => 'boolean',
            'invited_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->fullName())
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return HasMany<UserPermission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsToMany<Location, $this>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)->withTimestamps();
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'professional_service')->withTimestamps();
    }

    /**
     * @return HasOne<Professional, $this>
     */
    public function professionalProfile(): HasOne
    {
        return $this->hasOne(Professional::class);
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function customPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('allowed')
            ->withTimestamps();
    }

    public function fullName(): string
    {
        $fullName = trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));

        return $fullName !== '' ? $fullName : $this->name;
    }

    public function displayFirstName(): string
    {
        if ($this->first_name !== null && $this->first_name !== '') {
            return $this->first_name;
        }

        return (string) Str::of($this->name)->before(' ');
    }

    public function displayLastName(): string
    {
        if ($this->last_name !== null && $this->last_name !== '') {
            return $this->last_name;
        }

        $parts = Str::of($this->name)->explode(' ');

        return $parts->count() > 1 ? (string) $parts->slice(1)->implode(' ') : '';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path !== null ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function isAdministratorGeneral(): bool
    {
        return $this->role?->slug === UserRoleCatalog::GENERAL_ADMIN;
    }

    public function isAdministrator(): bool
    {
        return in_array($this->role?->slug, [
            UserRoleCatalog::GENERAL_ADMIN,
            UserRoleCatalog::LOCATION_ADMIN,
        ], true);
    }

    /**
     * @return list<int>
     */
    public function effectivePermissionIds(): array
    {
        $baseIds = $this->role?->permissions->pluck('id')->all() ?? [];
        $allowedIds = $this->permissions
            ->where('allowed', true)
            ->pluck('permission_id')
            ->all();
        $deniedIds = $this->permissions
            ->where('allowed', false)
            ->pluck('permission_id')
            ->all();

        return collect($baseIds)
            ->merge($allowedIds)
            ->reject(fn (mixed $id): bool => in_array((int) $id, $deniedIds, true))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->pipe(fn ($ids): array => array_values($ids->all()));
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isAdministratorGeneral()) {
            return true;
        }

        $permissionId = Permission::query()->where('slug', $slug)->value('id');

        if ($permissionId === null) {
            return false;
        }

        return in_array((int) $permissionId, $this->effectivePermissionIds(), true);
    }
}
