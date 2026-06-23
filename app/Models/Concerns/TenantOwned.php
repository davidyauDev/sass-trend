<?php

namespace App\Models\Concerns;

use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

trait TenantOwned
{
    use BelongsToTenant;
}
