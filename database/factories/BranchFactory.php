<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        $name = fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'address' => fake()->streetAddress(),
            'phone' => fake()->numerify('9########'),
            'email' => fake()->safeEmail(),
            'timezone' => 'America/Lima',
            'color' => fake()->randomElement(['sky', 'emerald', 'amber', 'violet']),
            'is_active' => true,
        ];
    }
}
