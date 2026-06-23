<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;
use App\Services\Services\ServiceCategoryManager;
use App\Services\Services\ServiceManagementGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UpdateServiceAction
{
    public function __construct(
        private readonly ServiceManagementGuard $guard,
        private readonly ServiceCategoryManager $categoryManager,
        private readonly SyncServiceProfessionalsAction $syncProfessionals,
        private readonly SaveServiceSchedulesAction $saveSchedules,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, Service $service, array $data): Service
    {
        $this->guard->ensureCanManage($actor);

        return DB::transaction(function () use ($service, $data): Service {
            $category = $this->categoryManager->resolve($data['service_category_id'], $data['new_category_name']);
            $imagePath = $service->image_path;

            if (($data['image'] ?? null) instanceof UploadedFile) {
                $newImagePath = $data['image']->store('services', 'public');

                if ($imagePath !== null) {
                    Storage::disk('public')->delete($imagePath);
                }

                $imagePath = $newImagePath;
            }

            $service->update([
                'service_category_id' => $category->id,
                'name' => $data['name'],
                'price' => $data['price'],
                'duration_minutes' => $data['duration_minutes'],
                'is_active' => $data['is_active'],
                'is_bookable_online' => $data['is_bookable_online'],
                'description' => $data['description'],
                'image_path' => $imagePath,
                'online_payment_type' => $data['online_payment_type'],
                'deposit_amount' => $data['deposit_amount'],
                'deposit_percentage' => $data['deposit_percentage'],
                'is_video_conference' => $data['is_video_conference'],
                'is_home_service' => $data['is_home_service'],
                'has_special_schedule' => $data['has_special_schedule'],
            ]);

            $this->syncProfessionals->handle($service, $data['professional_ids']);
            $this->saveSchedules->handle($service, $data['has_special_schedule'], $data['schedules']);

            return $service->load(['category', 'professionals', 'schedules']);
        });
    }
}
