<?php

namespace App\Livewire\Forms;

use App\Models\WebsiteSetting;
use Livewire\Form;

class WebsiteSettingsForm extends Form
{
    public string $site_name = '';

    public string $tagline = '';

    public string $description = '';

    public mixed $logo = null;

    public ?string $existingLogoPath = null;

    public mixed $hero_image = null;

    public ?string $existingHeroImagePath = null;

    public string $primary_color = '#4b3626';

    public string $currency_symbol = 'S/';

    public string $contact_phone = '';

    public string $contact_email = '';

    public string $whatsapp_phone = '';

    public string $instagram_url = '';

    public string $facebook_url = '';

    public string $tiktok_url = '';

    public string $website_url = '';

    public string $youtube_url = '';

    public string $booking_button_label = 'Reservar ahora';

    public string $booking_intro = '';

    public bool $is_active = false;

    public function fillFromSettings(WebsiteSetting $settings): void
    {
        $this->site_name = $settings->site_name;
        $this->tagline = $settings->tagline ?? '';
        $this->description = $settings->description ?? '';
        $this->logo = null;
        $this->existingLogoPath = $settings->logo_path;
        $this->hero_image = null;
        $this->existingHeroImagePath = $settings->hero_image_path;
        $this->primary_color = $settings->primary_color;
        $this->currency_symbol = $settings->currency_symbol;
        $this->contact_phone = $settings->contact_phone ?? '';
        $this->contact_email = $settings->contact_email ?? '';
        $this->whatsapp_phone = $settings->whatsapp_phone ?? '';
        $this->instagram_url = $settings->instagram_url ?? '';
        $this->facebook_url = $settings->facebook_url ?? '';
        $this->tiktok_url = $settings->tiktok_url ?? '';
        $this->website_url = $settings->website_url ?? '';
        $this->youtube_url = $settings->youtube_url ?? '';
        $this->booking_button_label = $settings->booking_button_label;
        $this->booking_intro = $settings->booking_intro ?? '';
        $this->is_active = $settings->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'site_name' => trim($this->site_name),
            'tagline' => $this->normalizeNullableString($this->tagline),
            'description' => $this->normalizeNullableString($this->description),
            'logo' => $this->logo,
            'hero_image' => $this->hero_image,
            'primary_color' => trim($this->primary_color),
            'currency_symbol' => trim($this->currency_symbol),
            'contact_phone' => $this->normalizeNullableString($this->contact_phone),
            'contact_email' => $this->normalizeNullableString($this->contact_email),
            'whatsapp_phone' => $this->normalizeNullableString($this->whatsapp_phone),
            'instagram_url' => $this->normalizeNullableString($this->instagram_url),
            'facebook_url' => $this->normalizeNullableString($this->facebook_url),
            'tiktok_url' => $this->normalizeNullableString($this->tiktok_url),
            'website_url' => $this->normalizeNullableString($this->website_url),
            'youtube_url' => $this->normalizeNullableString($this->youtube_url),
            'booking_button_label' => trim($this->booking_button_label),
            'booking_intro' => $this->normalizeNullableString($this->booking_intro),
            'is_active' => $this->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:50'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'booking_button_label' => ['required', 'string', 'max:80'],
            'booking_intro' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
