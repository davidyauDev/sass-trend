<?php

namespace App\Actions\Website;

use App\Models\Branch;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\WebsiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class UpdateWebsiteSettingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(WebsiteSetting $settings, array $data): WebsiteSetting
    {
        return DB::transaction(function () use ($settings, $data): WebsiteSetting {
            $logoPath = $settings->logo_path;
            $heroImagePath = $settings->hero_image_path;
            $galleryPaths = array_values($data['existing_gallery_paths'] ?? []);

            if (($data['logo'] ?? null) instanceof UploadedFile) {
                $newLogoPath = $data['logo']->store('website', 'public');

                if ($logoPath !== null) {
                    Storage::disk('public')->delete($logoPath);
                }

                $logoPath = $newLogoPath;
            }

            if (($data['hero_image'] ?? null) instanceof UploadedFile) {
                $newHeroImagePath = $data['hero_image']->store('website', 'public');

                if ($heroImagePath !== null) {
                    Storage::disk('public')->delete($heroImagePath);
                }

                $heroImagePath = $newHeroImagePath;
            }

            foreach ($data['gallery_uploads'] ?? [] as $galleryUpload) {
                if ($galleryUpload instanceof UploadedFile) {
                    $galleryPaths[] = $galleryUpload->store('website/gallery', 'public');
                }
            }

            $settings->update([
                'primary_location_id' => $data['primary_location_id'],
                'site_name' => $data['site_name'],
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'logo_path' => $logoPath,
                'hero_image_path' => $heroImagePath,
                'gallery_paths' => array_slice($galleryPaths, 0, 10),
                'amenities' => $data['amenities'],
                'highlights' => $data['highlights'],
                'directions' => $data['directions'],
                'primary_color' => $data['primary_color'],
                'currency_symbol' => $data['currency_symbol'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'],
                'whatsapp_phone' => $data['whatsapp_phone'],
                'instagram_url' => $data['instagram_url'],
                'facebook_url' => $data['facebook_url'],
                'tiktok_url' => $data['tiktok_url'],
                'website_url' => $data['website_url'],
                'youtube_url' => $data['youtube_url'],
                'booking_button_label' => $data['booking_button_label'],
                'booking_intro' => $data['booking_intro'],
                'instant_confirmation' => $data['instant_confirmation'],
                'is_active' => $data['is_active'],
            ]);

            $this->saveLocationSchedule($data);

            return $settings->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveLocationSchedule(array $data): void
    {
        $locationId = $data['primary_location_id'] ?? null;

        if (! is_int($locationId) || ! Location::query()->whereKey($locationId)->exists()) {
            return;
        }

        $location = Location::query()->findOrFail($locationId);
        $branchId = $location->branch_id;

        if ($branchId === null) {
            $activeBranches = Branch::query()->where('is_active', true)->limit(2)->get();
            $branchId = $activeBranches->count() === 1 ? $activeBranches->firstOrFail()->id : null;
        }

        $location->update([
            'address' => $data['location_address'],
            'branch_id' => $branchId,
        ]);

        foreach ($data['schedule'] ?? [] as $day => $hours) {
            $isOpen = (bool) ($hours['is_open'] ?? false);

            LocationSchedule::query()->updateOrCreate(
                ['location_id' => $locationId, 'day_of_week' => (int) $day],
                [
                    'is_open' => $isOpen,
                    'opens_at' => $isOpen ? ($hours['opens_at'] ?? '09:00') : null,
                    'closes_at' => $isOpen ? ($hours['closes_at'] ?? '18:00') : null,
                ],
            );
        }
    }
}
