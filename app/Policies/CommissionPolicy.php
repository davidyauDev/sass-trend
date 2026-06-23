<?php

namespace App\Policies;

use App\Models\ProfessionalCommission;
use App\Models\User;

class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('commissions.view');
    }

    public function view(User $user, ProfessionalCommission $commission): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('commissions.create');
    }

    public function update(User $user, ProfessionalCommission $commission): bool
    {
        return $user->is_active && $user->hasPermission('commissions.edit');
    }

    public function delete(User $user, ProfessionalCommission $commission): bool
    {
        return $user->is_active && $user->hasPermission('commissions.delete');
    }

    public function approve(User $user, ProfessionalCommission $commission): bool
    {
        return $user->is_active && $user->hasPermission('commissions.approve');
    }

    public function reject(User $user, ProfessionalCommission $commission): bool
    {
        return $user->is_active && $user->hasPermission('commissions.reject');
    }

    public function pay(User $user, ProfessionalCommission $commission): bool
    {
        return $user->is_active && $user->hasPermission('commissions.pay');
    }
}
