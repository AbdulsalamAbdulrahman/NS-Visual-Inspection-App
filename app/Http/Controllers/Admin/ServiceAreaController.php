<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InspectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceAreaRequest;
use App\Models\ServiceArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AD-08: add, rename, deactivate. Deactivated areas keep their history and
 * disappear from the contractor's Service area dropdown.
 */
class ServiceAreaController extends Controller
{
    public function index(): Response
    {
        $areas = ServiceArea::query()
            ->with(['reps' => fn ($q) => $q->select('users.id', 'users.name')->orderBy('users.name')])
            ->withCount([
                'inspections as inspections_total' => fn (Builder $q) => $q->where('status', InspectionStatus::Submitted),
                'inspections as inspections_this_month' => fn (Builder $q) => $q->where('status', InspectionStatus::Submitted)
                    ->where('submitted_at', '>=', now()->startOfMonth()),
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceArea $area): array => [
                'id' => $area->id,
                'name' => $area->name,
                'isActive' => $area->is_active,
                'reps' => $area->reps->pluck('name')->values(),
                'inspectionsTotal' => (int) ($area->inspections_total ?? 0),
                'inspectionsThisMonth' => (int) ($area->inspections_this_month ?? 0),
            ]);

        return Inertia::render('admin/Areas', ['areas' => $areas]);
    }

    public function store(ServiceAreaRequest $request): RedirectResponse
    {
        $area = ServiceArea::query()->create(['name' => $request->validated('name'), 'is_active' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$area->name} added."]);

        return back();
    }

    public function update(ServiceAreaRequest $request, ServiceArea $area): RedirectResponse
    {
        $area->update(['name' => $request->validated('name')]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Renamed to {$area->name}."]);

        return back();
    }

    public function toggle(ServiceArea $area): RedirectResponse
    {
        $area->update(['is_active' => ! $area->is_active]);

        Inertia::flash('toast', [
            'type' => $area->is_active ? 'success' : 'info',
            'message' => $area->is_active ? "{$area->name} reactivated." : "{$area->name} deactivated.",
        ]);

        return back();
    }
}
