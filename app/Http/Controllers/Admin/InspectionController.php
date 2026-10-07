<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ConnectionType;
use App\Enums\PropertyPurpose;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionReportResource;
use App\Http\Resources\InspectionRowResource;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Support\InspectionFilters;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AD-02 / AM-02 list with filters and CSV export, AD-03 / AM-04 detail.
 */
class InspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = InspectionFilters::fromRequest($request);

        $inspections = $filters->apply(Inspection::query()->submitted())
            ->with(['serviceArea:id,name', 'contractor:id,name', 'paidPayment'])
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $reviewCounts = Inspection::query()->submitted()
            ->selectRaw('review_status, count(*) as total')
            ->groupBy('review_status')
            ->pluck('total', 'review_status');

        return Inertia::render('admin/Inspections', [
            'inspections' => InspectionRowResource::collection($inspections),
            'totalSubmitted' => Inspection::query()->submitted()->count(),
            'reviewCounts' => collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $s): array => [$s->value => (int) ($reviewCounts[$s->value] ?? 0)]),
            'filters' => $filters->toArray(),
            'filterOptions' => self::filterOptions(),
        ]);
    }

    public function show(Inspection $inspection): Response
    {
        abort_unless($inspection->isSubmitted(), 404);

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'paidPayment', 'contractor.contractorProfile', 'reviews.user:id,name']);

        return Inertia::render('admin/InspectionShow', [
            'report' => InspectionReportResource::make($inspection),
            'printUrl' => route('inspections.print', $inspection),
            'certificateUrl' => $inspection->isApproved() ? route('inspections.certificate', $inspection) : null,
            'canReview' => Gate::allows('review', $inspection),
            'signatoryReady' => Setting::signatory() !== null,
        ]);
    }

    /** Streamed so large exports never hold everything in memory. */
    public function export(Request $request): StreamedResponse
    {
        $filters = InspectionFilters::fromRequest($request);

        $query = $filters->apply(Inspection::query()->submitted())
            ->with(['serviceArea:id,name', 'contractor:id,name', 'paidPayment'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                throw new RuntimeException('Could not open the CSV output stream.');
            }

            fwrite($out, "\u{FEFF}"); // UTF-8 BOM so Excel shows ₦ correctly.
            fputcsv($out, [
                'Ticket', 'Submitted', 'Owner', 'Address', 'Form 74 no.', 'Service area', 'Purpose', 'Connection',
                'Voltage', 'Contractor', 'NEMSA reg. no.', 'Amount paid', 'Monnify reference', 'GPS latitude', 'GPS longitude',
                'Review status', 'Approved',
            ]);

            // lazy() keeps the submitted_at order; lazyById() would re-sort by id and skip rows.
            foreach ($query->lazy(500) as $i) {
                $payment = $i->paidPayment;
                fputcsv($out, [
                    $i->ticket_no,
                    $i->submitted_at?->format('Y-m-d H:i'),
                    $i->owner_name,
                    $i->property_address ? (string) str($i->property_address)->squish() : null,
                    $i->form74_no,
                    $i->serviceArea?->name,
                    $i->purpose?->label(),
                    $i->connection_type?->label(),
                    $i->voltage_level?->label(),
                    $i->inspector_name,
                    $i->inspector_nemsa_reg_no,
                    $payment ? Money::format($payment->amount_paid_kobo ?? $payment->amount_kobo) : null,
                    $payment?->transaction_reference,
                    $i->gps_lat,
                    $i->gps_lng,
                    $i->review_status?->label(),
                    $i->approved_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, 'kens-inspections-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<int, int>|null  $areaIds  limit the area options (reps); null = all areas
     * @return array<string, mixed>
     */
    public static function filterOptions(?array $areaIds = null): array
    {
        return [
            'areas' => ServiceArea::query()
                ->when($areaIds !== null, fn ($q) => $q->whereIn('id', $areaIds))
                ->orderBy('name')
                ->get(['id', 'name']),
            'purpose' => PropertyPurpose::options(),
            'connection' => ConnectionType::options(),
        ];
    }
}
