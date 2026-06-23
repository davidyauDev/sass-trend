<?php

namespace App\Actions\Professionals;

use App\Models\Professional;
use App\Models\User;
use App\Services\Professionals\ProfessionalManagementGuard;
use Illuminate\Support\Facades\DB;

final class ToggleProfessionalStatusAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
    ) {}

    public function handle(User $actor, Professional $professional): Professional
    {
        $this->guard->ensureCanManage($actor);

        return DB::transaction(function () use ($professional): Professional {
            $newStatus = ! $professional->is_active;

            $professional->update([
                'is_active' => $newStatus,
                'accepts_online_bookings' => $newStatus ? $professional->accepts_online_bookings : false,
            ]);

            if ($professional->user !== null) {
                $professional->user->update([
                    'is_active' => $newStatus && $professional->has_system_access,
                ]);
            }

            return $professional->refresh();
        });
    }
}
