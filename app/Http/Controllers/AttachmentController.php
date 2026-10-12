<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** Serve a file only to people who can see its ticket (and, for internal notes, only to staff). */
    public function show(Request $request, Attachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment->ticket);

        if ($attachment->comment?->is_internal) {
            abort_unless($request->user()->isStaff(), 403);
        }

        $disposition = $attachment->isImage() || $attachment->mime_type === 'application/pdf' ? 'inline' : 'attachment';

        return Storage::disk(Attachment::DISK)->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff'],
            $disposition,
        );
    }
}
