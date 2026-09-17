<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalkInEntry>
 */
class WalkInEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'customer']),
            'technician_id' => null,
            'reference' => 'WI-'.fake()->unique()->regexify('[A-Z0-9]{8}'),
            'queue_number' => 'W'.fake()->unique()->numerify('###'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => '0917'.fake()->numerify('#######'),
            'service_type' => 'plumbing',
            'priority' => 'standard',
            'status' => 'waiting',
            'payment_method' => 'cash',
            'counter_id' => null,
            'notes' => fake()->sentence(),
            'checked_in_at' => now(),
        ];
    }
}
