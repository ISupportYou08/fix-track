<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WalkInEntry;
use Illuminate\View\View;

class WalkInReceiptController extends Controller
{
    public function __invoke(WalkInEntry $entry): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->canAccessOperationsWorkspace() || (int) $entry->user_id === (int) $user->id || (int) $entry->technician_id === (int) $user->id, 403);

        $entry->load(['payment.receiver:id,name', 'technician:id,name']);
        abort_unless($entry->payment?->status === 'paid', 404);

        return view('walk-in-receipt', ['entry' => $entry]);
    }
}
