<?php

namespace Database\Factories;

use App\Models\Professional;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Professional>
 */
class ProfessionalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'public_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'accepts_online_bookings' => true,
            'has_system_access' => false,
            'bio' => fake()->optional()->paragraph(),
            'photo_path' => null,
            'sale_commission' => fake()->randomFloat(2, 0, 50),
            'commission_type' => fake()->randomElement(['percent', 'amount']),
            'is_active' => true,
        ];
    }
}
