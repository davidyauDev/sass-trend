<?php

namespace App\Livewire\Forms;

use App\Models\WebsiteSetting;
use Livewire\Form;

class WebsiteSettingsForm extends Form
{
    public string $site_name = '';

    public string $tagline = '';

    public string $description = '';

    public ?int $primary_location_id = null;

    public string $location_address = '';

    public mixed $logo = null;

    public ?string $existingLogoPath = null;

    public mixed $hero_image = null;

    public ?string $existingHeroImagePath = null;

    /** @var array<int, mixed> */
    public array $gallery_uploads = [];

    /** @var list<string> */
    public array $existingGalleryPaths = [];

    /** @var list<string> */
    public array $amenities = [];

    /** @var list<string> */
    public array $highlights = [];

    public string $directions = '';

    /** @var array<int, array{is_open: bool, opens_at: string, closes_at: string}> */
    public array $schedule = [];

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

    public bool $instant_confirmation = true;

    public function fillFromSettings(WebsiteSetting $settings): void
    {
        $this->site_name = $settings->site_name;
        $this->tagline = $settings->tagline ?? '';
        $this->description = $settings->description ?? '';
        $this->primary_location_id = $settings->primary_location_id;
        $this->logo = null;
        $this->existingLogoPath = $settings->logo_path;
        $this->hero_image = null;
        $this->existingHeroImagePath = $settings->hero_image_path;
        $this->gallery_uploads = [];
        $this->existingGalleryPaths = array_values($settings->gallery_paths ?? []);
        $this->amenities = array_values($settings->amenities ?? []);
        $this->highlights = array_values($settings->highlights ?? []);
        $this->directions = $settings->directions ?? '';
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
        $this->instant_confirmation = $settings->instant_confirmation;
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
            'primary_location_id' => $this->primary_location_id,
            'location_address' => trim($this->location_address),
            'logo' => $this->logo,
            'hero_image' => $this->hero_image,
            'gallery_uploads' => $this->gallery_uploads,
            'existing_gallery_paths' => $this->existingGalleryPaths,
            'amenities' => $this->amenities,
            'highlights' => $this->highlights,
            'directions' => $this->normalizeNullableString($this->directions),
            'schedule' => $this->schedule,
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
            'instant_confirmation' => $this->instant_confirmation,
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
            'primary_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_address' => ['nullable', 'required_with:primary_location_id', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'gallery_uploads' => ['array', 'max:10'],
            'gallery_uploads.*' => ['image', 'max:6144'],
            'amenities' => ['array'],
            'amenities.*' => ['string', 'max:80'],
            'highlights' => ['array'],
            'highlights.*' => ['string', 'max:80'],
            'directions' => ['nullable', 'string', 'max:1000'],
            'schedule' => ['array'],
            'schedule.*.is_open' => ['boolean'],
            'schedule.*.opens_at' => ['nullable', 'date_format:H:i'],
            'schedule.*.closes_at' => ['nullable', 'date_format:H:i'],
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
            'instant_confirmation' => ['boolean'],
        ];
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
