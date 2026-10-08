<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The Head of New Service Department's name, title and signature, printed on
 * every certificate approved from now on (issued certificates keep a copy).
 */
class CertificateSettingsController extends Controller
{
    public function edit(): Response
    {
        $path = Setting::get(Setting::SIGNATORY_SIGNATURE);

        return Inertia::render('admin/CertificateSettings', [
            'signatory' => [
                'name' => Setting::get(Setting::SIGNATORY_NAME, ''),
                'title' => Setting::get(Setting::SIGNATORY_TITLE, 'Head, New Service Department'),
                'signatureUrl' => $path ? route('admin.certificate.signature').'?v='.md5($path) : null,
            ],
            'complete' => Setting::signatory() !== null,
            'pendingReview' => Inspection::query()->submitted()->where('review_status', ReviewStatus::Pending)->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hasSignature = filled(Setting::get(Setting::SIGNATORY_SIGNATURE));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:120'],
            'signature' => [$hasSignature ? 'nullable' : 'required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=200,min_height=60'],
        ], [
            'signature.required' => 'Upload a scan or photo of the signature.',
            'signature.dimensions' => 'The image is too small to print clearly (at least 200 × 60 px).',
        ]);

        $user = $request->user();
        Setting::put(Setting::SIGNATORY_NAME, trim($validated['name']), $user);
        Setting::put(Setting::SIGNATORY_TITLE, trim($validated['title']), $user);

        if (($file = $request->file('signature')) instanceof UploadedFile) {
            $old = Setting::get(Setting::SIGNATORY_SIGNATURE);
            $path = $file->storeAs('settings', 'nsd-signature-'.Str::random(10).'.'.$file->extension(), 'local');

            if ($path === false) {
                throw new RuntimeException('Could not store the signature image.');
            }

            Setting::put(Setting::SIGNATORY_SIGNATURE, $path, $user);

            // Issued certificates keep their own copy, so the old file can go.
            if ($old && $old !== $path) {
                Storage::disk('local')->delete($old);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Certificate signatory saved.']);

        return back();
    }

    public function signature(): StreamedResponse
    {
        $path = Setting::get(Setting::SIGNATORY_SIGNATURE);
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, 'signature', ['Cache-Control' => 'private, max-age=3600']);
    }
}
