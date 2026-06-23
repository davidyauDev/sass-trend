<?php

namespace App\Policies;

use App\Models\CommissionSettlement;
use App\Models\User;

class CommissionSettlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('settlements.view');
    }

    public function view(User $user, CommissionSettlement $settlement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('settlements.create');
    }

    public function approve(User $user, CommissionSettlement $settlement): bool
    {
        return $user->is_active && $user->hasPermission('settlements.approve');
    }

    public function pay(User $user, CommissionSettlement $settlement): bool
    {
        return $user->is_active && $user->hasPermission('settlements.pay');
    }
}
