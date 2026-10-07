<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inspections\SaveDraft;
use App\Actions\Inspections\StartInspection;
use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use App\Http\Requests\Inspections\SaveDraftRequest;
use App\Http\Resources\DraftResource;
use App\Http\Resources\InspectionCardResource;
use App\Http\Resources\InspectionReportResource;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contractor side: home (CH-01), start a draft, the 9-step form, auto-save.
 */
class InspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim($request->string('search')->toString());

        $drafts = $user->inspections()->drafts()->with('serviceArea:id,name')->latest('updated_at')->get();

        $submitted = $user->inspections()->submitted()
            ->with('serviceArea:id,name')
            ->search($search)
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('contractor/Home', [
            'drafts' => InspectionCardResource::collection($drafts),
            'submitted' => InspectionCardResource::collection($submitted),
            'submittedTotal' => $user->inspections()->submitted()->count(),
            'filters' => ['search' => $search],
            'fee' => ($kobo = FeeSchedule::currentAmountKobo()) ? Money::format($kobo) : null,
        ]);
    }

    public function store(Request $request, StartInspection $start): RedirectResponse
    {
        $validated = $request->validate(['uuid' => ['nullable', 'uuid', 'unique:inspections,uuid']]);

        $inspection = $start->handle($request->user(), $validated['uuid'] ?? null);

        return to_route('inspections.edit', $inspection);
    }

    /** CD-01: the contractor's own submitted report, read-only. */
    public function show(Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('view', $inspection);

        if ($inspection->isDraft()) {
            return to_route('inspections.edit', $inspection);
        }

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'paidPayment', 'contractor.contractorProfile']);

        return Inertia::render('contractor/InspectionShow', [
            'report' => InspectionReportResource::make($inspection),
            'printUrl' => Route::has('inspections.print') ? route('inspections.print', $inspection) : null,
            'paid' => $inspection->paidPayment ? Money::format($inspection->paidPayment->amount_paid_kobo ?? $inspection->paidPayment->amount_kobo) : null,
        ]);
    }

    public function edit(Request $request, Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('view', $inspection);

        if (! $request->user()->can('update', $inspection)) {
            // Submitted inspections are locked: show the read-only report.
            Inertia::flash('toast', ['type' => 'info', 'message' => 'This inspection has been submitted and can no longer be edited.']);

            return to_route('inspections.show', $inspection);
        }

        $inspection->load(['circuits', 'attachments', 'serviceArea']);
        $profile = $request->user()->contractorProfile;
        $fee = FeeSchedule::current();

        return Inertia::render('contractor/InspectionForm', [
            'inspection' => DraftResource::make($inspection),
            'step' => min(max($request->integer('step', $inspection->current_step), 1), Inspection::STEPS),
            'areas' => ServiceArea::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $inspection->service_area_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'options' => [
                'purpose' => PropertyPurpose::options(),
                'connectionType' => ConnectionType::options(),
                'voltageLevel' => VoltageLevel::options(),
                'protectionType' => ProtectionType::options(),
                'earthingSystemType' => EarthingSystemType::options(),
                'conductorType' => ConductorType::options(),
                'wiringMethod' => WiringMethod::options(),
                'circuitDescription' => CircuitDescription::options(),
                'circuitCondition' => CircuitCondition::options(),
            ],
            'inspector' => [
                'name' => $request->user()->name,
                'category' => $profile?->nemsa_category->label(),
                'regNo' => $profile?->nemsa_reg_no,
                'corenNo' => $profile?->coren_no,
                'firmName' => $profile?->firm_name,
            ],
            'fee' => $fee ? [
                'amount' => Money::format($fee->amount_kobo),
                'effectiveFrom' => $fee->effective_from->format('d M Y'),
            ] : null,
        ]);
    }

    /**
     * Auto-save / Save draft (JSON). Upserts so a draft started offline with
     * a phone-generated uuid is created on its first sync.
     */
    public function saveDraft(SaveDraftRequest $request, string $uuid, SaveDraft $save, StartInspection $start): JsonResponse
    {
        abort_unless(Str::isUuid($uuid), 404);

        $inspection = Inspection::query()->where('uuid', $uuid)->first()
            ?? $start->handle($request->user(), $uuid);

        Gate::authorize('update', $inspection);

        $save->handle($inspection, $request->validated());

        return response()->json([
            'uuid' => $inspection->uuid,
            'saved_at' => $inspection->updated_at?->toIso8601String(),
            'signature_url' => $inspection->signature_path
                ? route('inspections.signature', $inspection).'?v='.md5($inspection->signature_path)
                : null,
        ]);
    }

    public function signature(Inspection $inspection): StreamedResponse
    {
        Gate::authorize('view', $inspection);

        $disk = Storage::disk('local');
        abort_unless($inspection->signature_path && $disk->exists($inspection->signature_path), 404);

        return $disk->response($inspection->signature_path, 'signature.png', [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
