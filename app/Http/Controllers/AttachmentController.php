<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inspections\StoreAttachment;
use App\Enums\AttachmentType;
use App\Http\Requests\Inspections\StoreAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Inspection;
use App\Models\InspectionAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uploads (contractor, draft only) and the authorised file route every role
 * uses to view photos and PDFs. There is no public storage link.
 */
class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Inspection $inspection, StoreAttachment $store): JsonResponse
    {
        $attachment = $store->handle(
            $inspection,
            AttachmentType::from($request->validated('type')),
            $request->file('file'),
            $request->safe()->only(['exif_lat', 'exif_lng', 'taken_at']),
        );

        $inspection->touch();

        return AttachmentResource::make($attachment)->response()->setStatusCode(201);
    }

    public function destroy(Inspection $inspection, InspectionAttachment $attachment): JsonResponse
    {
        abort_unless($attachment->inspection_id === $inspection->id, 404);
        Gate::authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
        $inspection->touch();

        return response()->json(status: 204);
    }

    public function show(Request $request, InspectionAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $attachment);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->path), 404);

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }
}
