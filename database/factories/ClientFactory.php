<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->optional()->dateTimeBetween('-70 years', '-18 years'),
            'age' => fake()->optional()->numberBetween(18, 70),
            'dni' => fake()->boolean(70) ? fake()->unique()->numerify('########') : null,
            'gender' => fake()->optional()->randomElement(['Femenino', 'Masculino', 'No binario', 'Prefiero no decirlo']),
            'client_number' => fake()->boolean(60) ? fake()->unique()->bothify('CLI-####') : null,
            'email' => fake()->boolean(75) ? fake()->unique()->safeEmail() : null,
            'phone' => fake()->boolean(80) ? fake()->numerify('9########') : null,
            'address' => fake()->boolean(70) ? fake()->streetAddress() : null,
            'district' => fake()->boolean(70) ? fake()->citySuffix() : null,
            'city' => fake()->boolean(70) ? fake()->city() : null,
        ];
    }
}
