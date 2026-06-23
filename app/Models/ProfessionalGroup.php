<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $location_id
 * @property string $name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'name',
    'is_active',
])]
class ProfessionalGroup extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsToMany<Professional, $this>
     */
    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class, 'professional_group_members')->withTimestamps();
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

        return $query->where(function (Builder $groupQuery) use ($like): void {
            $groupQuery
                ->where('name', 'like', $like)
                ->orWhereHas('location', fn (Builder $locationQuery): Builder => $locationQuery->where('name', 'like', $like));
        });
    }
}
