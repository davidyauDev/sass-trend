<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'sale_number' => (string) fake()->unique()->numberBetween(1000, 9999),
            'ticket_number' => (string) fake()->unique()->numberBetween(42250000, 99999999),
            'sold_at' => now(),
            'status' => 'paid',
            'subtotal' => 100,
            'discount_total' => 0,
            'total' => 100,
            'paid_total' => 100,
            'change_total' => 0,
            'notes' => null,
        ];
    }
}
