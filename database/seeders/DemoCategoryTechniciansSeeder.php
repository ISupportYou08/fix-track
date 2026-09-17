<?php

namespace Database\Seeders;

use App\Models\ServiceCatalog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoCategoryTechniciansSeeder extends Seeder
{
    private const SAMPLE_PASSWORD = 'FixTrack123!';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $categories = ServiceCatalog::activeCatalog()->groupBy('category')->sortKeys();

        if ($categories->isEmpty()) {
            throw new RuntimeException('The service catalog must be seeded before category technicians.');
        }

        foreach ($categories->values() as $index => $services) {
            $category = (string) $services->first()?->category;
            $slug = Str::slug($category);
            $email = "technician-{$slug}@fixtrack.test";
            $technician = User::query()->firstOrNew(['email' => $email]);

            if ($technician->exists && ! $technician->isTechnician()) {
                throw new RuntimeException("The account {$email} already belongs to a non-technician user.");
            }

            $phone = '+63919'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT);
            $technician->fill([
                'name' => "Sample {$category} Technician",
                'role' => 'technician',
                'phone' => $phone,
                'password' => Hash::make(self::SAMPLE_PASSWORD),
                'account_status' => 'active',
                'availability_status' => 'available',
                'last_seen_at' => $now,
                'email_verified_at' => $now,
            ]);
            $technician->save();

            $technician->technicianVerification()->updateOrCreate([], [
                'status' => 'approved',
                'risk_level' => 'low',
                'service_categories' => $services->pluck('code')->values()->all(),
                'years_experience' => 5,
                'walk_in_rating' => 4.85,
                'service_area' => 'Metro Manila',
                'phone' => $phone,
                'address' => "{$category} Service Center, Metro Manila",
                'decision_reason' => "Approved sample technician for the {$category} category.",
                'submitted_at' => $now,
                'reviewed_at' => $now,
            ]);
        }
    }
}
