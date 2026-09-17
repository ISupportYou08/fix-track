<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoWalkInShopsSeeder extends Seeder
{
    private const SAMPLE_PASSWORD = 'FixTrack123!';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            ['address' => '145 España Boulevard, Manila, Metro Manila', 'latitude' => 14.6083, 'longitude' => 120.9873],
            ['address' => '220 Rizal Avenue, Manila, Metro Manila', 'latitude' => 14.6068, 'longitude' => 120.9821],
            ['address' => '88 Taft Avenue, Manila, Metro Manila', 'latitude' => 14.5894, 'longitude' => 120.9848],
            ['address' => '72 Tomas Morato Avenue, Quezon City, Metro Manila', 'latitude' => 14.6342, 'longitude' => 121.0325],
            ['address' => '56 Gil Puyat Avenue, Makati City, Metro Manila', 'latitude' => 14.5637, 'longitude' => 121.0253],
        ];
        $now = Carbon::now();

        foreach (ServiceCatalog::activeCatalog()->pluck('category')->unique()->values() as $category) {
            for ($shopNumber = 1; $shopNumber <= 5; $shopNumber++) {
                $location = $locations[$shopNumber - 1];
                $slug = Str::slug((string) $category);
                $shop = User::query()->firstOrNew(['email' => "walkin-{$slug}-{$shopNumber}@fixtrack.test"]);

                $shop->fill([
                    'name' => "{$category} Shop {$shopNumber}",
                    'role' => 'technician',
                    'phone' => '+63918'.str_pad((string) $shopNumber, 7, '0', STR_PAD_LEFT),
                    'account_status' => 'active',
                    'availability_status' => 'available',
                    'password' => Hash::make(self::SAMPLE_PASSWORD),
                    'last_seen_at' => $now,
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                ]);

                $shop->email_verified_at = $now;
                $shop->save();

                $shop->technicianVerification()->updateOrCreate([], [
                    'status' => 'approved',
                    'risk_level' => 'low',
                    'service_categories' => [$slug],
                    'years_experience' => 3 + $shopNumber,
                    'walk_in_rating' => 5 - (($shopNumber - 1) * 0.15),
                    'service_area' => 'Metro Manila',
                    'phone' => $shop->phone,
                    'address' => $location['address'],
                    'decision_reason' => 'Approved sample walk-in shop.',
                    'submitted_at' => $now,
                    'reviewed_at' => $now,
                ]);
            }
        }
    }
}
