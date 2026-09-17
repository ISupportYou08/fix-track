<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceCatalog;
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
        $activeWalkIns = WalkInEntry::query()->whereIn('status', WalkInEntry::ACTIVE_STATUSES)->count();

        return view('welcome', [
            'services' => $services,
            'statistics' => [
                ['label' => 'Services completed', 'value' => Booking::query()->where('status', 'completed')->count()],
                ['label' => 'Available services', 'value' => ServiceCatalog::activeCatalog()->count()],
                ['label' => 'Active technicians', 'value' => User::query()->where('role', 'technician')->where('account_status', 'active')->count()],
                ['label' => 'Customers served', 'value' => User::query()->where('role', 'customer')->count()],
            ],
            'walkInCapacity' => WalkInEntry::MAX_ACTIVE,
            'walkInAvailableSlots' => max(0, WalkInEntry::MAX_ACTIVE - $activeWalkIns),
        ]);
    }
}
