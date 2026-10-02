<?php

namespace App\Http\Controllers;

use App\Models\BookingItemAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingItemImageController extends Controller
{
    public function __invoke(BookingItemAnalysis $analysis): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $booking = $analysis->booking()->with('technician')->firstOrFail();
        $canViewMatchingRequest = $user->isTechnician()
            && $user->hasActiveAccount()
            && $booking->assigned_technician_id === null
            && in_array($booking->status, ['pending', 'matching'], true)
            && $user->technicianVerification?->supportsService((string) $booking->service_type);
        $isAuthorized = $user->canAccessOperationsWorkspace()
            || (int) $booking->user_id === (int) $user->id
            || (int) $booking->assigned_technician_id === (int) $user->id
            || $canViewMatchingRequest;

        abort_unless($isAuthorized, 403);
        $imageIndex = request()->integer('image');
        $image = $analysis->imageFiles()[$imageIndex] ?? null;
        abort_unless(is_array($image), 404);
        abort_unless(Storage::disk('local')->exists($image['path']), 404);

        return Storage::disk('local')->response(
            $image['path'],
            $image['original_name'],
            ['Content-Type' => $image['mime_type']],
        );
    }
}
