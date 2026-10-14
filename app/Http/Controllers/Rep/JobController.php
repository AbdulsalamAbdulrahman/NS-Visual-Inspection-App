<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rep;

use App\Actions\Jobs\ImportJobs;
use App\Actions\Jobs\SaveJob;
use App\Enums\BuildingType;
use App\Enums\InspectionStatus;
use App\Enums\PropertyPurpose;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rep\JobRequest;
use App\Http\Resources\JobResource;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reps create jobs for the customers in their area offices and assign each
 * to a contractor in the area's state; one at a time or from a CSV. A job
 * can be changed or reassigned until the contractor starts it.
 */
class JobController extends Controller
{
    private const IMPORT_SESSION_KEY = 'rep.job_import';

    public function index(Request $request): Response
    {
        $rep = $request->user();
        $progress = $request->string('progress')->toString();

        $base = Inspection::query()->jobsFor($rep);

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $jobs = (clone $base)
            ->when($progress === 'not_started', fn ($q) => $q->where('status', InspectionStatus::Assigned))
            ->when($progress === 'in_progress', fn ($q) => $q->where('status', InspectionStatus::Draft))
            ->when($progress === 'submitted', fn ($q) => $q->where('status', InspectionStatus::Submitted))
            ->search($request->string('search')->toString())
            ->with(['serviceArea:id,name', 'contractor:id,uuid,name', 'contractor.contractorProfile:id,user_id,licence_expires_at', 'assigner:id,name'])
            ->latest('assigned_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('rep/Jobs', [
            'jobs' => JobResource::collection($jobs),
            'counts' => [
                'all' => (int) $counts->sum(),
                'not_started' => (int) ($counts[InspectionStatus::Assigned->value] ?? 0),
                'in_progress' => (int) ($counts[InspectionStatus::Draft->value] ?? 0),
                'submitted' => (int) ($counts[InspectionStatus::Submitted->value] ?? 0),
            ],
            'filters' => ['progress' => $progress ?: null, 'search' => $request->string('search')->toString()],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('rep/JobForm', [...$this->formProps($request->user()), 'job' => null]);
    }

    public function store(JobRequest $request, SaveJob $save): RedirectResponse
    {
        $job = $save->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Job for {$job->owner_name} assigned to {$job->contractor->name}."]);

        return to_route('rep.jobs.index');
    }

    public function edit(Request $request, Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('changeJob', $inspection);

        $inspection->load(['serviceArea:id,name', 'contractor:id,uuid,name', 'assigner:id,name']);

        return Inertia::render('rep/JobForm', [...$this->formProps($request->user()), 'job' => JobResource::make($inspection)]);
    }

    public function update(JobRequest $request, Inspection $inspection, SaveJob $save): RedirectResponse
    {
        Gate::authorize('changeJob', $inspection);

        $job = $save->handle($request->user(), $request->validated(), $inspection);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Job for {$job->owner_name} updated."]);

        return to_route('rep.jobs.index');
    }

    /** Cancel a job the contractor hasn't started. */
    public function destroy(Inspection $inspection): RedirectResponse
    {
        Gate::authorize('changeJob', $inspection);

        $inspection->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Job for {$inspection->owner_name} cancelled."]);

        return to_route('rep.jobs.index');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ImportJobs::COLUMNS, escape: '');
            fputcsv($out, ['Amina Sule', '14 Ahmadu Bello Way, Kaduna', '08031234567', 'amina.sule@example.com', 'Barnawa', 'Residential', 'Duplex', 'F74/KD/2026/08841', '', 'contractor@example.com'], escape: '');
            fputcsv($out, ['Sabon Gari Estate Ltd', 'Plot 3, Sabon Gari Estate', '08059876543', 'estates@example.com', 'Barnawa', 'Residential', 'Block of flats', 'F74/KD/2026/09001', 'F74/KD/2026/09020', 'contractor@example.com'], escape: '');
            fclose($out);
        }, 'kens-jobs-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Check the uploaded CSV and show the preview; nothing is saved yet. */
    public function upload(Request $request, ImportJobs $import): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:2048', 'mimes:csv,txt']], ['file.mimes' => 'Upload the CSV template (.csv).']);

        try {
            $rows = $import->read($request->user(), $request->file('file')->getRealPath());
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $request->session()->put(self::IMPORT_SESSION_KEY, ['name' => $request->file('file')->getClientOriginalName(), 'rows' => $rows]);

        return to_route('rep.jobs.import');
    }

    public function preview(Request $request): Response|RedirectResponse
    {
        $import = $request->session()->get(self::IMPORT_SESSION_KEY);
        /** @var list<array{line: int, summary: array<string, string|null>, errors: list<string>}> $rows */
        $rows = is_array($import) ? $import['rows'] : [];

        return Inertia::render('rep/JobImport', [
            'fileName' => is_array($import) ? $import['name'] : null,
            'rows' => array_map(fn (array $row): array => [
                'line' => $row['line'],
                ...$row['summary'],
                'errors' => $row['errors'],
            ], $rows),
            'columns' => ImportJobs::COLUMNS,
            'maxRows' => ImportJobs::MAX_ROWS,
        ]);
    }

    public function confirm(Request $request, ImportJobs $import): RedirectResponse
    {
        $pending = $request->session()->pull(self::IMPORT_SESSION_KEY);

        if (! is_array($pending) || empty($pending['rows'])) {
            return to_route('rep.jobs.import');
        }

        $created = $import->create($request->user(), $pending['rows']);
        $skipped = count($pending['rows']) - $created;

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$created} job".($created === 1 ? '' : 's').' created'.($skipped > 0 ? ", {$skipped} skipped" : '').'.',
        ]);

        return to_route('rep.jobs.index');
    }

    public function discard(Request $request): RedirectResponse
    {
        $request->session()->forget(self::IMPORT_SESSION_KEY);

        return to_route('rep.jobs.import');
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(User $rep): array
    {
        $areas = $rep->serviceAreas()->orderBy('name')->get();
        $states = $areas->map(fn (ServiceArea $area): string => $area->state->value)->unique()->values();

        $contractors = User::query()
            ->role(Role::Contractor)
            ->where('status', UserStatus::Active)
            ->whereHas('contractorProfile', fn ($q) => $q->whereIn('state', $states)->licensed())
            ->with('contractorProfile')
            ->withCount(['inspections as open_jobs' => fn ($q) => $q->whereIn('status', [InspectionStatus::Assigned, InspectionStatus::Draft])])
            ->orderBy('name')
            ->get();

        return [
            'areas' => $areas->map(fn (ServiceArea $area): array => [
                'id' => $area->id,
                'name' => $area->name,
                'state' => $area->state->value,
                'stateLabel' => $area->state->label(),
            ])->values(),
            'contractors' => $contractors->map(fn (User $c): array => [
                'uuid' => $c->uuid,
                'name' => $c->name,
                'firm' => $c->contractorProfile?->firm_name,
                'category' => $c->contractorProfile?->nemsa_category->label(),
                'state' => $c->contractorProfile?->state?->value,
                'openJobs' => (int) $c->getAttribute('open_jobs'),
            ])->values(),
            'options' => [
                'purpose' => PropertyPurpose::options(),
                'buildingType' => BuildingType::options(),
            ],
        ];
    }
}
