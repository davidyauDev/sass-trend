<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;
use App\Services\Services\ServiceManagementGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class DeleteServiceAction
{
    public function __construct(
        private readonly ServiceManagementGuard $guard,
    ) {}

    public function handle(User $actor, Service $service): string
    {
        $this->guard->ensureCanManage($actor);

        if ($this->hasAssociatedBookings($service)) {
            $service->update([
                'is_active' => false,
            ]);

            return 'deactivated';
        }

        if ($service->image_path !== null) {
            Storage::disk('public')->delete($service->image_path);
        }

        $service->delete();

        return 'deleted';
    }

    private function hasAssociatedBookings(Service $service): bool
    {
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'service_id')) {
            return DB::table('appointments')->where('service_id', $service->id)->exists();
        }

        return false;
    }
}
