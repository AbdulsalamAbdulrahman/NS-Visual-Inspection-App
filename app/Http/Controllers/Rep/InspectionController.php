<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rep;

use App\Http\Controllers\Admin\InspectionController as AdminInspections;
use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionReportResource;
use App\Http\Resources\InspectionRowResource;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Support\InspectionFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

/**
 * SR-D1 / SR-T1 / SR-M1 list scoped to the rep's areas, SR-M3 detail. The
 * visibleTo scope and InspectionPolicy both enforce the area limit.
 */
class InspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $rep = $request->user()->loadMissing('serviceAreas');
        $filters = InspectionFilters::fromRequest($request);
        $myAreaIds = $rep->serviceAreas->map(fn (ServiceArea $area): int => $area->id)->values()->all();

        $base = Inspection::query()->visibleTo($rep);

        $inspections = $filters->apply(clone $base)
            ->with(['serviceArea:id,name', 'contractor:id,name'])
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        $perArea = (clone $base)->selectRaw('service_area_id, count(*) as total')->groupBy('service_area_id')->pluck('total', 'service_area_id');

        return Inertia::render('rep/Inspections', [
            'inspections' => InspectionRowResource::collection($inspections),
            'areas' => $rep->serviceAreas->map(fn ($a): array => [
                'id' => $a->id,
                'name' => $a->name,
                'count' => (int) ($perArea[$a->id] ?? 0),
            ])->values(),
            'total' => (int) $perArea->sum(),
            'filters' => $filters->toArray(),
            'filterOptions' => AdminInspections::filterOptions($myAreaIds),
        ]);
    }

    public function show(Inspection $inspection): Response
    {
        Gate::authorize('view', $inspection);

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'contractor.contractorProfile']);

        return Inertia::render('rep/InspectionShow', [
            'report' => InspectionReportResource::make($inspection),
            'printUrl' => Route::has('inspections.print') ? route('inspections.print', $inspection) : null,
        ]);
    }
}
