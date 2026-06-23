<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->boolean(70) ? fake()->numerify('9########') : null,
            'role_id' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'is_primary_admin' => false,
            'invited_at' => null,
            'invitation_accepted_at' => null,
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function administratorGeneral(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->firstOrCreate(
                ['slug' => UserRoleCatalog::GENERAL_ADMIN],
                collect(UserRoleCatalog::definitions())
                    ->firstWhere('slug', UserRoleCatalog::GENERAL_ADMIN),
            )->id,
            'is_active' => true,
        ]);
    }
}
