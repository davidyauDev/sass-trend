<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' '.fake()->randomElement(['Centro', 'Norte', 'Sur']),
            'site_name' => fake()->company(),
            'tagline' => fake()->sentence(6),
            'address' => fake()->streetAddress(),
            'phone' => fake()->boolean(80) ? fake()->numerify('9########') : null,
            'email' => fake()->boolean(70) ? fake()->unique()->companyEmail() : null,
            'timezone' => fake()->randomElement(['America/Lima', 'America/Bogota', 'America/Mexico_City']),
            'accepts_online_bookings' => fake()->boolean(),
            'secondary_phone' => fake()->boolean(40) ? fake()->numerify('9########') : null,
            'description' => fake()->boolean(60) ? fake()->sentence(12) : null,
            'image_path' => null,
            'logo_path' => null,
            'hero_image_path' => null,
            'primary_color' => fake()->hexColor(),
            'contact_phone' => fake()->boolean(60) ? fake()->numerify('9########') : null,
            'contact_email' => fake()->boolean(60) ? fake()->unique()->companyEmail() : null,
            'whatsapp_phone' => fake()->boolean(50) ? fake()->numerify('51#########') : null,
            'instagram_url' => fake()->boolean(40) ? fake()->url() : null,
            'facebook_url' => fake()->boolean(40) ? fake()->url() : null,
            'tiktok_url' => fake()->boolean(40) ? fake()->url() : null,
            'booking_button_label' => 'Reservar ahora',
            'booking_intro' => fake()->boolean(60) ? fake()->sentence(10) : null,
            'is_active' => fake()->boolean(85),
        ];
    }
}
