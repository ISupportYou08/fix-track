<?php

namespace App\Http\Controllers;

use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportAttachmentController extends Controller
{
    public function __invoke(SupportMessage $message): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->canAccessOperationsWorkspace() || (int) $message->ticket?->user_id === (int) $user->id, 403);
        abort_unless($message->attachment_path !== null && Storage::disk('local')->exists($message->attachment_path), 404);

        return Storage::disk('local')->download($message->attachment_path, $message->attachment_name ?: 'attachment');
    }
}
