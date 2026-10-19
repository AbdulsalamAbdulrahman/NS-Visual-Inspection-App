<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Accounts\SaveContractorDocuments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ContractorDocumentsRequest;
use App\Models\ContractorProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A contractor's licence copy, passport photograph and signature: uploaded by
 * the contractor on Profile, viewable by them and by NSD admins only.
 */
class ContractorDocumentController extends Controller
{
    public function store(ContractorDocumentsRequest $request, SaveContractorDocuments $save): RedirectResponse
    {
        $profile = $request->user()->contractorProfile;
        abort_if($profile === null, 404);

        $save->handle($profile, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return back();
    }

    /** The signed-in contractor's own document. */
    public function show(Request $request, string $document): StreamedResponse
    {
        return $this->stream($request->user()->contractorProfile, $document);
    }

    /** NSD admins view any contractor's documents (route is admin-only). */
    public function forAdmin(User $contractor, string $document): StreamedResponse
    {
        abort_unless($contractor->isContractor(), 404);

        return $this->stream($contractor->contractorProfile, $document);
    }

    private function stream(?ContractorProfile $profile, string $document): StreamedResponse
    {
        $path = $profile?->documentPath($document);
        $disk = Storage::disk('local');
        abort_unless($path !== null && $disk->exists($path), 404);

        return $disk->response($path, $document.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
