<?php

namespace App\Actions\Locations;

use App\Models\Location;
use App\Services\Locations\LocationLimitService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CreateLocationAction
{
    public function __construct(
        private readonly LocationLimitService $locationLimitService,
        private readonly UpsertLocationBranchAction $upsertLocationBranch,
        private readonly SaveLocationSchedulesAction $saveLocationSchedules,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Location
    {
        $this->locationLimitService->ensureCanCreate(Auth::user());

        return DB::transaction(function () use ($data): Location {
            $branch = $this->upsertLocationBranch->handle($data);
            $logoPath = ($data['logo'] ?? null) instanceof UploadedFile
                ? $data['logo']->store('locations/logos', 'public')
                : null;

            $heroImagePath = ($data['hero_image'] ?? null) instanceof UploadedFile
                ? $data['hero_image']->store('locations/hero-images', 'public')
                : null;

            $location = Location::create([
                'name' => $data['name'],
                'site_name' => $data['site_name'] !== null && $data['site_name'] !== '' ? $data['site_name'] : $data['name'],
                'tagline' => $data['tagline'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'timezone' => $data['timezone'],
                'branch_id' => $branch->id,
                'description' => $data['description'],
                'logo_path' => $logoPath,
                'hero_image_path' => $heroImagePath,
                'primary_color' => $data['primary_color'] !== '' ? $data['primary_color'] : '#4b3626',
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'],
                'whatsapp_phone' => $data['whatsapp_phone'],
                'instagram_url' => $data['instagram_url'],
                'facebook_url' => $data['facebook_url'],
                'tiktok_url' => $data['tiktok_url'],
                'booking_button_label' => $data['booking_button_label'] !== '' ? $data['booking_button_label'] : 'Reservar ahora',
                'booking_intro' => $data['booking_intro'],
                'accepts_online_bookings' => $data['accepts_online_bookings'],
                'secondary_phone' => $data['secondary_phone'],
                'is_active' => $data['is_active'],
            ]);

            $this->saveLocationSchedules->handle($location, $data['schedules']);

            return $location->load('schedules');
        });
    }
}
