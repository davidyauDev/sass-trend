<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => fake()->randomElement(['Limpieza facial', 'Masaje relajante', 'Consulta inicial']),
            'price' => fake()->randomFloat(2, 0, 300),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'is_active' => true,
            'is_bookable_online' => true,
            'description' => fake()->boolean(60) ? fake()->sentence(12) : null,
            'image_path' => null,
            'online_payment_type' => fake()->randomElement([null, 'not_allowed', 'allowed', 'required', 'deposit_required']),
            'deposit_amount' => null,
            'deposit_percentage' => null,
            'is_video_conference' => false,
            'is_home_service' => false,
            'has_special_schedule' => false,
        ];
    }
}
