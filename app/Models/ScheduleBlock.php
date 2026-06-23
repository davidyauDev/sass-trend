<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $branch_id
 * @property int|null $resource_id
 * @property int|null $user_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property string $block_type
 * @property string|null $reason
 * @property bool $is_all_day
 * @property string|null $recurrence_rule
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'branch_id',
    'resource_id',
    'user_id',
    'starts_at',
    'ends_at',
    'block_type',
    'reason',
    'is_all_day',
    'recurrence_rule',
    'created_by',
    'updated_by',
])]
class ScheduleBlock extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',
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
     * @return BelongsTo<ResourceModel, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceModel::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
