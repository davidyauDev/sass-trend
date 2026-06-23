<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use App\Models\Resource as ResourceModel;
use Database\Factories\BranchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string $timezone
 * @property string|null $color
 * @property bool $is_active
 */
#[Fillable([
    'name',
    'slug',
    'address',
    'phone',
    'email',
    'timezone',
    'color',
    'is_active',
])]
class Branch extends Model
{
    /** @use HasFactory<BranchFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ResourceModel, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ResourceModel::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<ScheduleBlock, $this>
     */
    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class);
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

        return $query->where(function (Builder $branchQuery) use ($like): void {
            $branchQuery
                ->where('name', 'like', $like)
                ->orWhere('address', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
