<?php

use App\Models\ServiceCatalog;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Database\Seeders\DemoCategoryTechniciansSeeder;
use Database\Seeders\DemoServiceCatalogSeeder;
use Database\Seeders\DemoWalkInShopsSeeder;
use Illuminate\Support\Facades\Hash;

test('demo seeders provide accounts, one hundred services, and five walk-in shops per category', function () {
    $this->seed([
        DemoServiceCatalogSeeder::class,
        DemoAccountsSeeder::class,
        DemoCategoryTechniciansSeeder::class,
        DemoWalkInShopsSeeder::class,
    ]);

    $administrator = User::query()->where('email', 'admin@fixtrack.test')->firstOrFail();
    $user = User::query()->where('email', 'user@fixtrack.test')->firstOrFail();
    $customer = User::query()->where('email', 'customer@fixtrack.test')->firstOrFail();
    $technician = User::query()->where('email', 'technician@fixtrack.test')->firstOrFail();
    $categoryTechnicians = User::query()
        ->with('technicianVerification')
        ->where('email', 'like', 'technician-%@fixtrack.test')
        ->get();

    expect($administrator->isAdmin())->toBeTrue()
        ->and($user->isCustomer())->toBeTrue()
        ->and($customer->isCustomer())->toBeTrue()
        ->and($technician->isTechnician())->toBeTrue()
        ->and($technician->technicianVerification?->status)->toBe('approved')
        ->and(Hash::check('FixTrack123!', $administrator->password))->toBeTrue()
        ->and(Hash::check('FixTrack123!', $user->password))->toBeTrue()
        ->and($categoryTechnicians)->toHaveCount(10)
        ->and(ServiceCatalog::query()->where('is_active', true)->count())->toBe(100)
        ->and(ServiceCatalog::query()->where('is_active', true)->distinct()->count('category'))->toBe(10)
        ->and(User::query()->where('email', 'like', 'walkin-%@fixtrack.test')->count())->toBe(50);

    foreach ($categoryTechnicians as $categoryTechnician) {
        $verification = $categoryTechnician->technicianVerification;

        expect($verification?->status)->toBe('approved')
            ->and($verification?->service_categories)->not->toBeEmpty()
            ->and(Hash::check('FixTrack123!', $categoryTechnician->password))->toBeTrue();
    }
});
