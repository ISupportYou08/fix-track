<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechnicianDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TechnicianDocumentPreviewController extends Controller
{
    public function __invoke(TechnicianDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()?->canAccessOperationsWorkspace(), 403);
        abort_unless(filled($document->file_path), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->file_path), 404);

        $officeMimeType = null;
        if ($document->type === 'credentials') {
            $officeMimeType = match (strtolower(pathinfo($document->label, PATHINFO_EXTENSION))) {
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                default => null,
            };
        }

        if ($officeMimeType !== null) {
            return $disk->download($document->file_path, basename($document->label), [
                'Content-Type' => $officeMimeType,
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        $mimeType = $disk->mimeType($document->file_path);
        abort_unless(in_array($mimeType, [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ], true), 404);

        return $disk->response($document->file_path, basename($document->label), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
