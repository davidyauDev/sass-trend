<?php

namespace App\Models;

use App\Models\Concerns\TenantOwned;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $site_name
 * @property string|null $tagline
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $hero_image_path
 * @property string $primary_color
 * @property string $currency_symbol
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $whatsapp_phone
  * @property string|null $instagram_url
  * @property string|null $facebook_url
  * @property string|null $tiktok_url
 * @property string|null $website_url
 * @property string|null $youtube_url
 * @property string $booking_button_label
 * @property string|null $booking_intro
 * @property bool $is_active
 */
#[Fillable([
    'site_name',
    'tagline',
    'description',
    'logo_path',
    'hero_image_path',
    'primary_color',
    'currency_symbol',
    'contact_phone',
    'contact_email',
    'whatsapp_phone',
    'instagram_url',
    'facebook_url',
    'tiktok_url',
    'website_url',
    'youtube_url',
    'booking_button_label',
    'booking_intro',
    'is_active',
])]
class WebsiteSetting extends Model
{
    use TenantOwned;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'Trend Belleza',
            'tagline' => 'Reserva tus servicios en linea',
            'description' => 'Explora nuestros servicios, elige a tu profesional y agenda en minutos.',
            'logo_path' => null,
            'hero_image_path' => null,
            'primary_color' => '#4b3626',
            'currency_symbol' => 'S/',
            'contact_phone' => null,
            'contact_email' => null,
            'whatsapp_phone' => null,
            'instagram_url' => null,
            'facebook_url' => null,
            'tiktok_url' => null,
            'website_url' => null,
            'youtube_url' => null,
            'booking_button_label' => 'Reservar ahora',
            'booking_intro' => 'Selecciona local, servicio, profesional y horario para confirmar tu reserva.',
            'is_active' => false,
        ];
    }

    public static function current(): self
    {
        return self::query()->first() ?? self::query()->create(self::defaults());
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path !== null ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function heroImageUrl(): ?string
    {
        return $this->hero_image_path !== null ? Storage::disk('public')->url($this->hero_image_path) : null;
    }
}
