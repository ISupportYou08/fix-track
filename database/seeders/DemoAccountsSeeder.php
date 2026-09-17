<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoAccountsSeeder extends Seeder
{
    private const SAMPLE_PASSWORD = 'FixTrack123!';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $accounts = [
            [
                'name' => 'Sample Administrator',
                'email' => 'admin@fixtrack.test',
                'role' => 'admin',
                'phone' => '+639170000001',
            ],
            [
                'name' => 'Sample Customer',
                'email' => 'user@fixtrack.test',
                'role' => 'customer',
                'phone' => '+639170000002',
            ],
            [
                'name' => 'Sample Customer',
                'email' => 'customer@fixtrack.test',
                'role' => 'customer',
                'phone' => '+639171234567',
            ],
            [
                'name' => 'Sample Technician',
                'email' => 'technician@fixtrack.test',
                'role' => 'technician',
                'phone' => '+639179876543',
            ],
        ];

        foreach ($accounts as $accountData) {
            $account = User::query()->firstOrNew(['email' => $accountData['email']]);

            if ($account->exists && $account->role !== $accountData['role']) {
                throw new RuntimeException("The account {$accountData['email']} already belongs to a different role.");
            }

            $account->fill([
                ...$accountData,
                'password' => Hash::make(self::SAMPLE_PASSWORD),
                'account_status' => 'active',
                'availability_status' => $accountData['role'] === 'technician' ? 'available' : 'offline',
            ]);

            $account->email_verified_at = $now;
            $account->save();

            if ($account->isTechnician()) {
                $account->technicianVerification()->updateOrCreate([], [
                    'status' => 'approved',
                    'risk_level' => 'low',
                    'service_categories' => ['cooling', 'appliances', 'electrical', 'electronics', 'plumbing'],
                    'years_experience' => 5,
                    'service_area' => 'Metro Manila',
                    'phone' => $account->phone,
                    'address' => 'Quezon City, Metro Manila',
                    'decision_reason' => 'Approved sample technician account.',
                    'submitted_at' => $now,
                    'reviewed_at' => $now,
                ]);
            }
        }
    }
}
