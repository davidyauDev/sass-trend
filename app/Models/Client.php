<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $birth_date
 * @property int|null $age
 * @property string|null $dni
 * @property string|null $gender
 * @property string|null $client_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $district
 * @property string|null $city
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'first_name',
    'last_name',
    'birth_date',
    'age',
    'dni',
    'gender',
    'client_number',
    'email',
    'phone',
    'address',
    'district',
    'city',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, TenantOwned;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'age' => 'integer',
        ];
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
