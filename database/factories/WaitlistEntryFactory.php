<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WaitlistEntry> */
class WaitlistEntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'client_id' => Client::factory(),
            'service_id' => Service::factory(),
            'professional_id' => null,
            'appointment_id' => null,
            'desired_date' => fake()->dateTimeBetween('today', '+30 days'),
            'available_from' => '09:00',
            'available_until' => '18:00',
            'status' => WaitlistEntry::STATUS_WAITING,
            'notes' => null,
            'booked_at' => null,
            'created_by' => null,
        ];
    }
}
