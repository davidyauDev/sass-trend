<?php

namespace App\Actions\Users;

use App\Models\User;

final class AssignUserLocationsAction
{
    /**
     * @param  list<int>  $locationIds
     */
    public function handle(User $user, array $locationIds): void
    {
        $user->locations()->sync($locationIds);
    }
}
