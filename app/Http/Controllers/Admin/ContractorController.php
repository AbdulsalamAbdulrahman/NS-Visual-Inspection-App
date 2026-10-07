<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\CreateContractor;
use App\Actions\Accounts\UpdateContractor;
use App\Enums\InspectionStatus;
use App\Enums\NemsaCategory;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContractorRequest;
use App\Http\Resources\Admin\ContractorResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $contractors = User::query()
            ->role(Role::Contractor)
            ->with('contractorProfile')
            ->withCount(['inspections' => fn (Builder $q) => $q->where('status', InspectionStatus::Submitted)])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(fn (Builder $q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhereHas('contractorProfile', fn (Builder $p) => $p->where('nemsa_reg_no', 'like', $like)));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $counts = User::query()->role(Role::Contractor)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/Contractors', [
            'contractors' => ContractorResource::collection($contractors),
            'filters' => ['search' => $search],
            'summary' => [
                'total' => (int) $counts->sum(),
                'active' => (int) ($counts[UserStatus::Active->value] ?? 0),
                'invited' => (int) ($counts[UserStatus::Invited->value] ?? 0),
                'suspended' => (int) ($counts[UserStatus::Suspended->value] ?? 0),
            ],
            'categories' => collect(NemsaCategory::cases())->map(fn (NemsaCategory $c): array => [
                'value' => $c->value,
                'label' => $c->label(),
            ]),
        ]);
    }

    public function store(ContractorRequest $request, CreateContractor $create): RedirectResponse
    {
        $contractor = $create->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Login details sent to {$contractor->email}"]);
        Inertia::flash('highlight', $contractor->uuid);

        return to_route('admin.contractors.index');
    }

    public function update(ContractorRequest $request, User $contractor, UpdateContractor $update): RedirectResponse
    {
        abort_unless($contractor->isContractor(), 404);

        $update->handle($contractor, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$contractor->name} updated."]);

        return back();
    }
}
