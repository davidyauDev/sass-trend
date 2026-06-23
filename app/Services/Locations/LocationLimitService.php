<?php

namespace App\Services\Locations;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

final class LocationLimitService
{
    public function canCreate(?Authenticatable $user = null): bool
    {
        return true;
    }

    public function ensureCanCreate(?Authenticatable $user = null): void
    {
        if ($this->canCreate($user)) {
            return;
        }

        throw ValidationException::withMessages([
            'form.name' => 'Tu plan actual no permite registrar otro local.',
        ]);
    }
}
