<?php

namespace App\Actions\Professionals;

use App\Models\Professional;
use App\Models\User;
use App\Services\Professionals\ProfessionalManagementGuard;
use Illuminate\Support\Facades\DB;

final class DeleteProfessionalAction
{
    public function __construct(
        private readonly ProfessionalManagementGuard $guard,
    ) {}

    public function handle(User $actor, Professional $professional): void
    {
        $this->guard->ensureCanManage($actor);

        DB::transaction(function () use ($professional): void {
            $professional->update([
                'is_active' => false,
                'accepts_online_bookings' => false,
                'has_system_access' => false,
            ]);

            if ($professional->user !== null) {
                $professional->user->update([
                    'is_active' => false,
                ]);
            }
        });
    }
}
