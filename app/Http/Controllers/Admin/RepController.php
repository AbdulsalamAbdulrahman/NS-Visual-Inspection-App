<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\SaveRep;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RepRequest;
use App\Http\Resources\Admin\RepResource;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RepController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $reps = User::query()
            ->role(Role::Rep)
            ->with('serviceAreas')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('serviceAreas', fn (Builder $a) => $a->where('name', 'like', $like)));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        // Area picker: every active area, with who already covers it (AD-07).
        $areas = ServiceArea::query()
            ->active()
            ->with(['reps' => fn ($q) => $q->select('users.id', 'users.name')])
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceArea $area): array => [
                'id' => $area->id,
                'name' => $area->name,
                'reps' => $area->reps->pluck('name')->values(),
            ]);

        return Inertia::render('admin/Reps', [
            'reps' => RepResource::collection($reps),
            'filters' => ['search' => $search],
            'areas' => $areas,
        ]);
    }

    public function store(RepRequest $request, SaveRep $save): RedirectResponse
    {
        $rep = $save->handle($request->repData());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Login details sent to {$rep->email}"]);
        Inertia::flash('highlight', $rep->uuid);

        return to_route('admin.reps.index');
    }

    public function update(RepRequest $request, User $rep, SaveRep $save): RedirectResponse
    {
        abort_unless($rep->isRep(), 404);

        $save->handle($request->repData(), $rep);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$rep->name} updated."]);

        return back();
    }
}
