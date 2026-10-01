<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceCatalog;
use App\Models\TechnicianVerification;
use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        $services = ServiceCatalog::activeCatalog()->take(6);
        $shopIds = TechnicianVerification::query()
            ->where('status', 'approved')
            ->whereIn('service_type', ['walkin', 'both'])
            ->whereHas('technician', fn ($query) => $query->where('account_status', 'active'))
            ->pluck('user_id');
        $walkInCapacity = max(1, $shopIds->count()) * WalkInEntry::MAX_ACTIVE;
        $activeWalkIns = WalkInEntry::query()
            ->whereIn('status', WalkInEntry::ACTIVE_STATUSES)
            ->when($shopIds->isNotEmpty(), fn ($query) => $query->whereIn('technician_id', $shopIds))
            ->count();

        return view('welcome', [
            'services' => $services,
            'statistics' => [
                ['label' => 'Services completed', 'value' => Booking::query()->where('status', 'completed')->count()],
                ['label' => 'Available services', 'value' => ServiceCatalog::activeCatalog()->count()],
                ['label' => 'Active technicians', 'value' => User::query()->where('role', 'technician')->where('account_status', 'active')->count()],
                ['label' => 'Customers served', 'value' => User::query()->where('role', 'customer')->count()],
            ],
            'walkInCapacity' => $walkInCapacity,
            'walkInAvailableSlots' => max(0, $walkInCapacity - $activeWalkIns),
        ]);
    }
}
