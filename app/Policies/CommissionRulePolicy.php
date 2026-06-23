<?php

namespace App\Policies;

use App\Models\CommissionRule;
use App\Models\User;

class CommissionRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('commissions.manage_rules');
    }

    public function view(User $user, CommissionRule $rule): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('commissions.manage_rules');
    }

    public function update(User $user, CommissionRule $rule): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, CommissionRule $rule): bool
    {
        return $this->create($user);
    }
}
