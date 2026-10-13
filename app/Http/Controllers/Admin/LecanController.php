<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\SaveLecanOfficial;
use App\Enums\NigerianState;
use App\Enums\ReviewStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LecanRequest;
use App\Http\Resources\Admin\LecanResource;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * LECAN officials: one or more per state chapter; they review reports in
 * their state before NSD gives final approval.
 */
class LecanController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $officials = User::query()
            ->role(Role::Lecan)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('lecan_state')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        // Reports waiting for each chapter, so a state with no official stands out.
        $waiting = Inspection::query()
            ->submitted()
            ->where('review_status', ReviewStatus::PendingLecan)
            ->join('service_areas', 'service_areas.id', '=', 'inspections.service_area_id')
            ->selectRaw('service_areas.state as state, count(*) as total')
            ->groupBy('service_areas.state')
            ->pluck('total', 'state');

        $staffed = User::query()->role(Role::Lecan)->where('status', '!=', 'suspended')->pluck('lecan_state')->map(fn ($s) => $s instanceof NigerianState ? $s->value : $s)->all();
        $statesInUse = ServiceArea::query()->distinct()->pluck('state')->map(fn ($s) => $s instanceof NigerianState ? $s->value : $s)->all();

        return Inertia::render('admin/LecanOfficials', [
            'officials' => LecanResource::collection($officials),
            'filters' => ['search' => $search],
            'states' => collect(NigerianState::cases())->map(fn (NigerianState $state): array => [
                'value' => $state->value,
                'label' => $state->label(),
                'waiting' => (int) ($waiting[$state->value] ?? 0),
                'staffed' => in_array($state->value, $staffed, true),
                'hasAreas' => in_array($state->value, $statesInUse, true),
            ])->values(),
        ]);
    }

    public function store(LecanRequest $request, SaveLecanOfficial $save): RedirectResponse
    {
        $official = $save->handle($request->officialData());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Login details sent to {$official->email}"]);
        Inertia::flash('highlight', $official->uuid);

        return to_route('admin.lecan.index');
    }

    public function update(LecanRequest $request, User $official, SaveLecanOfficial $save): RedirectResponse
    {
        abort_unless($official->isLecan(), 404);

        $save->handle($request->officialData(), $official);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$official->name} updated."]);

        return back();
    }
}
