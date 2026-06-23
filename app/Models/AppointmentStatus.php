<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property int $sort_order
 * @property bool $is_terminal
 */
#[Fillable([
    'name',
    'slug',
    'color',
    'sort_order',
    'is_terminal',
])]
class AppointmentStatus extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_terminal' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
