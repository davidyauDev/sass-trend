<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $owner_name
 * @property string $owner_email
 * @property string $plan
 * @property string $status
 * @property string|null $provisioning_error
 * @property Carbon|null $provisioned_at
 * @property Carbon|null $suspended_at
 */
#[Fillable([
    'id',
    'name',
    'slug',
    'owner_name',
    'owner_email',
    'plan',
    'status',
    'provisioning_error',
    'provisioned_at',
    'suspended_at',
])]
class Tenant extends BaseTenant
{
    use HasDomains;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_FAILED = 'failed';

    public const PLAN_BASIC = 'basic';

    public const PLAN_PRO = 'pro';

    public const PLAN_ENTERPRISE = 'enterprise';

    /**
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return array_values(array_merge(parent::getCustomColumns(), [
            'name',
            'slug',
            'owner_name',
            'owner_email',
            'plan',
            'status',
            'provisioning_error',
            'provisioned_at',
            'suspended_at',
            'created_at',
            'updated_at',
        ]));
    }

    /**
     * @return HasMany<Domain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'tenant_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provisioned_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, never>
     */
    protected function primaryDomain(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->domains()->oldest()->value('domain'));
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
