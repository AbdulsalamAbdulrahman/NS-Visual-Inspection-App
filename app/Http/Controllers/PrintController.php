<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CertificateResource;
use App\Http\Resources\InspectionReportResource;
use App\Models\Inspection;
use App\Models\User;
use App\Support\TicketQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A4 print pages, the same for every role (access still checked by role and
 * area): the two-page report (PR-01 / PR-02) and the one-page certificate.
 * Printed with the browser's own Print / Save as PDF.
 */
class PrintController extends Controller
{
    public function report(Request $request, Inspection $inspection): Response
    {
        Gate::authorize('print', $inspection);

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'contractor.contractorProfile']);

        return Inertia::render('print/Report', [
            'report' => InspectionReportResource::make($inspection)->forPrint(),
            'qr' => TicketQr::dataUri((string) $inspection->ticket_no),
            'generatedAt' => now()->format('d M Y, H:i'),
            'backUrl' => $this->backUrl($request->user(), $inspection),
            'certificateUrl' => $request->user()->can('certificate', $inspection) ? route('inspections.certificate', $inspection) : null,
            'autoPrint' => $request->boolean('pdf'),
        ]);
    }

    public function certificate(Request $request, Inspection $inspection): Response
    {
        Gate::authorize('certificate', $inspection);

        $inspection->load(['serviceArea', 'contractor.contractorProfile']);

        return Inertia::render('print/Certificate', [
            'certificate' => CertificateResource::make($inspection),
            'backUrl' => $this->backUrl($request->user(), $inspection),
            'reportUrl' => route('inspections.print', $inspection),
            'autoPrint' => $request->boolean('pdf'),
        ]);
    }

    /** The NSD signatory's signature as copied onto this certificate at approval. */
    public function signatorySignature(Inspection $inspection): StreamedResponse
    {
        Gate::authorize('certificate', $inspection);

        $disk = Storage::disk('local');
        $path = $inspection->signatory_signature_path;
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, 'nsd-signature', ['Cache-Control' => 'private, max-age=86400']);
    }

    private function backUrl(User $user, Inspection $inspection): string
    {
        return match (true) {
            $user->isAdmin() => route('admin.inspections.show', $inspection),
            $user->isRep() => route('rep.inspections.show', $inspection),
            default => route('inspections.show', $inspection),
        };
    }
}
