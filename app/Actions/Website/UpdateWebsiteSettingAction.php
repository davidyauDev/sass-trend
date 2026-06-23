<?php

namespace App\Actions\Website;

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

            $settings->update([
                'site_name' => $data['site_name'],
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'logo_path' => $logoPath,
                'hero_image_path' => $heroImagePath,
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
                'is_active' => $data['is_active'],
            ]);

            return $settings->refresh();
        });
    }
}
