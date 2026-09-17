<?php

namespace Database\Factories;

use App\Models\WalkInEntry;
use App\Models\WalkInStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalkInStatusHistory>
 */
class WalkInStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'walk_in_entry_id' => WalkInEntry::factory(),
            'from_status' => 'waiting',
            'to_status' => 'called',
            'actor_id' => null,
            'reason' => fake()->sentence(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
