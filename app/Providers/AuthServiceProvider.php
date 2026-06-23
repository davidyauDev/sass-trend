<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\CommissionRule;
use App\Models\CommissionSettlement;
use App\Models\ProfessionalCommission;
use App\Models\Resource;
use App\Models\ScheduleBlock;
use App\Models\Service;
use App\Policies\AppointmentPolicy;
use App\Policies\CommissionPolicy;
use App\Policies\CommissionRulePolicy;
use App\Policies\CommissionSettlementPolicy;
use App\Policies\ResourcePolicy;
use App\Policies\ScheduleBlockPolicy;
use App\Policies\ServicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        ProfessionalCommission::class => CommissionPolicy::class,
        CommissionRule::class => CommissionRulePolicy::class,
        CommissionSettlement::class => CommissionSettlementPolicy::class,
        Resource::class => ResourcePolicy::class,
        Service::class => ServicePolicy::class,
        ScheduleBlock::class => ScheduleBlockPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('commissions.approve', fn ($user): bool => $user->hasPermission('commissions.approve'));
        Gate::define('commissions.export', fn ($user): bool => $user->hasPermission('commissions.export'));
        Gate::define('commissions.manage_rules', fn ($user): bool => $user->hasPermission('commissions.manage_rules'));
        Gate::define('commissions.pay', fn ($user): bool => $user->hasPermission('commissions.pay'));
    }
}
