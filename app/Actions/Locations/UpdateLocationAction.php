<?php

namespace App\Actions\Locations;

use App\Models\Location;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UpdateLocationAction
{
    public function __construct(
        private readonly SaveLocationSchedulesAction $saveLocationSchedules,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Location $location, array $data): Location
    {
        return DB::transaction(function () use ($location, $data): Location {
            $logoPath = $location->logo_path;
            $heroImagePath = $location->hero_image_path;

            if (($data['logo'] ?? null) instanceof UploadedFile) {
                $newLogoPath = $data['logo']->store('locations/logos', 'public');

                if ($logoPath !== null) {
                    Storage::disk('public')->delete($logoPath);
                }

                $logoPath = $newLogoPath;
            }

            if (($data['hero_image'] ?? null) instanceof UploadedFile) {
                $newHeroImagePath = $data['hero_image']->store('locations/hero-images', 'public');

                if ($heroImagePath !== null) {
                    Storage::disk('public')->delete($heroImagePath);
                }

                $heroImagePath = $newHeroImagePath;
            }

            $location->update([
                'name' => $data['name'],
                'site_name' => $data['site_name'] !== null && $data['site_name'] !== '' ? $data['site_name'] : $data['name'],
                'tagline' => $data['tagline'],
                'address' => $data['address'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'timezone' => $data['timezone'],
                'branch_id' => $data['branch_id'],
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
