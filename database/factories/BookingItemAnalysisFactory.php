<?php

namespace Database\Factories;

use App\Models\BookingItemAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingItemAnalysis>
 */
class BookingItemAnalysisFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => 'booking-item-images/example.jpg',
            'image_original_name' => 'item.jpg',
            'image_mime_type' => 'image/jpeg',
            'detected_item_name' => fake()->randomElement(['Smartphone', 'Air conditioner', 'Television']),
            'suggested_service_code' => 'electronics-diagnosis',
            'suggested_service_category' => 'Electronics',
            'confidence' => fake()->randomFloat(4, 0.7, 0.99),
            'explanation' => 'The image appears to show a supported household item.',
            'model' => 'gpt-4.1-mini',
            'confirmed_at' => now(),
        ];
    }
}
