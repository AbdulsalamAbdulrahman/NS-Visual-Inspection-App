<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CertificateResource;
use App\Http\Resources\InspectionReportResource;
use App\Http\Resources\ReceiptResource;
use App\Models\Inspection;
use App\Models\InspectionAttachment;
use App\Support\DocumentUrls;
use App\Support\TicketQr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The building owner's copies, opened from emailed signed links (no account):
 * the receipt once paid, and the certificate and full report once approved.
 * Same print pages as signed-in users, without the way back into the app.
 */
class CustomerDocumentController extends Controller
{
    public function receipt(Request $request, Inspection $inspection): Response
    {
        $this->asCustomer($request);
        $inspection->load(['serviceArea', 'paidPayment', 'contractor.contractorProfile']);
        abort_unless($inspection->isSubmitted() && $inspection->paidPayment !== null, 404);

        return Inertia::render('print/Receipt', [
            'receipt' => ReceiptResource::make($inspection),
            'backUrl' => null,
            'autoPrint' => false,
        ]);
    }

    public function certificate(Request $request, Inspection $inspection): Response
    {
        $this->asCustomer($request);
        abort_unless($inspection->isApproved(), 404);
        $inspection->load(['serviceArea', 'contractor.contractorProfile']);

        return Inertia::render('print/Certificate', [
            'certificate' => CertificateResource::make($inspection),
            'backUrl' => null,
            'reportUrl' => DocumentUrls::forOwner('report', $inspection),
            'autoPrint' => false,
        ]);
    }

    public function report(Request $request, Inspection $inspection): Response
    {
        $this->asCustomer($request);
        abort_unless($inspection->isApproved(), 404);
        $inspection->load(['serviceArea', 'circuits', 'attachments', 'contractor.contractorProfile']);

        return Inertia::render('print/Report', [
            'report' => InspectionReportResource::make($inspection)->forPrint(),
            'qr' => TicketQr::dataUri((string) $inspection->ticket_no),
            'generatedAt' => now()->format('d M Y, H:i'),
            'backUrl' => null,
            'certificateUrl' => DocumentUrls::forOwner('certificate', $inspection),
            'autoPrint' => false,
        ]);
    }

    public function signature(Inspection $inspection): StreamedResponse
    {
        abort_unless($inspection->isSubmitted(), 404);

        return $this->image($inspection->signature_path, 'signature.png');
    }

    public function signatory(Inspection $inspection): StreamedResponse
    {
        abort_unless($inspection->isApproved(), 404);

        return $this->image($inspection->signatory_signature_path, 'nsd-signature');
    }

    public function attachment(InspectionAttachment $attachment): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($attachment->inspection->isApproved() && $disk->exists($attachment->path), 404);

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    /** Images on these pages get signed links too (see DocumentUrls). */
    private function asCustomer(Request $request): void
    {
        $request->attributes->set(DocumentUrls::CUSTOMER_VIEW, true);
    }

    private function image(?string $path, string $name): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, $name, ['Cache-Control' => 'private, max-age=86400']);
    }
}
