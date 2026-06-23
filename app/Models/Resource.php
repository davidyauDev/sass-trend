<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $user_id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $color
 * @property int $capacity
 * @property bool $is_shared
 * @property bool $is_active
 * @property string|null $notes
 */
#[Fillable([
    'branch_id',
    'user_id',
    'name',
    'slug',
    'type',
    'color',
    'capacity',
    'is_shared',
    'is_active',
    'notes',
])]
class Resource extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_shared' => 'boolean',
            'is_active' => 'boolean',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

        return $query->where(function (Builder $resourceQuery) use ($like): void {
            $resourceQuery
                ->where('name', 'like', $like)
                ->orWhere('slug', 'like', $like)
                ->orWhere('type', 'like', $like)
                ->orWhereHas('branch', fn (Builder $branchQuery): Builder => $branchQuery->where('name', 'like', $like))
                ->orWhereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('name', 'like', $like));
        });
    }
}
