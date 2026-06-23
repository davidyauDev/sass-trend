<?php

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('resources.view');
    }

    public function view(User $user, Resource $resource): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('resources.create');
    }

    public function update(User $user, Resource $resource): bool
    {
        return $user->is_active && $user->hasPermission('resources.update');
    }

    public function delete(User $user, Resource $resource): bool
    {
        return $user->is_active && $user->hasPermission('resources.delete');
    }
}
