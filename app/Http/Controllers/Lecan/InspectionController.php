<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lecan;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionReportResource;
use App\Http\Resources\InspectionRowResource;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LECAN officials: reports in their state chapter, the ones waiting for them
 * first, and the review of each (approve for NSD or send back).
 */
class InspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $official = $request->user();
        $review = ReviewStatus::tryFrom($request->string('review')->toString());
        // Default view: the queue waiting for LECAN.
        $queue = ! $request->has('review');

        $base = Inspection::query()->visibleTo($official);

        $counts = (clone $base)->selectRaw('review_status, count(*) as total')->groupBy('review_status')->pluck('total', 'review_status');

        $inspections = (clone $base)
            ->when($queue, fn ($q) => $q->where('review_status', ReviewStatus::PendingLecan))
            ->when($review, fn ($q) => $q->where('review_status', $review))
            ->search($request->string('search')->toString())
            ->with(['serviceArea:id,name', 'contractor:id,name'])
            ->when($queue, fn ($q) => $q->oldest('submitted_at'), fn ($q) => $q->latest('submitted_at'))
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('lecan/Inspections', [
            'inspections' => InspectionRowResource::collection($inspections),
            'chapter' => $official->lecan_state?->label(),
            'counts' => [
                'all' => (int) $counts->sum(),
                ...collect(ReviewStatus::cases())->mapWithKeys(fn (ReviewStatus $s): array => [$s->value => (int) ($counts[$s->value] ?? 0)]),
            ],
            'filters' => [
                'review' => $queue ? ReviewStatus::PendingLecan->value : ($review->value ?? 'all'),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(Request $request, Inspection $inspection): Response
    {
        Gate::authorize('view', $inspection);

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'contractor.contractorProfile', 'reviews.user:id,name,role', 'reviewer:id,role']);

        return Inertia::render('lecan/InspectionShow', [
            'report' => InspectionReportResource::make($inspection),
            'printUrl' => route('inspections.print', $inspection),
            'certificateUrl' => $inspection->isApproved() ? route('inspections.certificate', $inspection) : null,
            'canReview' => Gate::allows('review', $inspection),
        ]);
    }
}
