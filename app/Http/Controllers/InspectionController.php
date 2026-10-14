<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Inspections\SaveDraft;
use App\Actions\Inspections\StartInspection;
use App\Actions\Reviews\ResubmitInspection;
use App\Enums\BuildingType;
use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\FuseType;
use App\Enums\InspectionStatus;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\ReviewStatus;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use App\Http\Requests\Inspections\SaveDraftRequest;
use App\Http\Resources\DraftResource;
use App\Http\Resources\InspectionCardResource;
use App\Http\Resources\InspectionReportResource;
use App\Http\Resources\JobResource;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Support\InspectionFee;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contractor side: home (CH-01), start an assigned job, the 9-step form, auto-save.
 */
class InspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = trim($request->string('search')->toString());

        $assigned = $user->inspections()->where('status', InspectionStatus::Assigned)
            ->with(['serviceArea:id,name', 'contractor:id,uuid,name', 'assigner:id,name'])->oldest('assigned_at')->get();
        $drafts = $user->inspections()->drafts()->with('serviceArea:id,name')->latest('updated_at')->get();
        $returned = $user->inspections()->submitted()->where('review_status', ReviewStatus::ChangesRequested)
            ->with('serviceArea:id,name')->latest('reviewed_at')->get();

        $submitted = $user->inspections()->submitted()
            ->with('serviceArea:id,name')
            ->search($search)
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('contractor/Home', [
            'assigned' => JobResource::collection($assigned),
            'drafts' => InspectionCardResource::collection($drafts),
            'returned' => InspectionCardResource::collection($returned),
            'submitted' => InspectionCardResource::collection($submitted),
            'submittedTotal' => $user->inspections()->submitted()->count(),
            'filters' => ['search' => $search],
            'fee' => ($kobo = FeeSchedule::currentAmountKobo()) ? Money::format($kobo) : null,
        ]);
    }

    /** At the property: the assigned job becomes a draft and the form opens. */
    public function start(Inspection $inspection, StartInspection $start): RedirectResponse
    {
        Gate::authorize('start', $inspection);

        $start->handle($inspection);

        return to_route('inspections.edit', $inspection);
    }

    /** CD-01: the contractor's own submitted report, read-only. */
    public function show(Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('view', $inspection);

        if ($inspection->isDraft()) {
            return to_route('inspections.edit', $inspection);
        }

        if ($inspection->isAssigned()) {
            return $this->notStarted();
        }

        $inspection->load(['serviceArea', 'circuits', 'attachments', 'paidPayment', 'contractor.contractorProfile', 'reviewer:id,role']);

        return Inertia::render('contractor/InspectionShow', [
            'report' => InspectionReportResource::make($inspection),
            'printUrl' => route('inspections.print', $inspection),
            'certificateUrl' => $inspection->isApproved() ? route('inspections.certificate', $inspection) : null,
            'editUrl' => $inspection->needsChanges() ? route('inspections.edit', $inspection) : null,
            'paid' => $inspection->paidPayment ? Money::format($inspection->paidPayment->amount_paid_kobo ?? $inspection->paidPayment->amount_kobo) : null,
        ]);
    }

    public function edit(Request $request, Inspection $inspection): Response|RedirectResponse
    {
        Gate::authorize('view', $inspection);

        if ($inspection->isAssigned()) {
            return $this->notStarted();
        }

        if (! $request->user()->can('update', $inspection)) {
            // Submitted inspections are locked: show the read-only report.
            Inertia::flash('toast', ['type' => 'info', 'message' => 'This inspection has been submitted and can no longer be edited.']);

            return to_route('inspections.show', $inspection);
        }

        $inspection->load(['circuits', 'attachments', 'serviceArea']);
        $profile = $request->user()->contractorProfile;

        return Inertia::render('contractor/InspectionForm', [
            'inspection' => DraftResource::make($inspection),
            'step' => min(max($request->integer('step', $inspection->current_step), 1), Inspection::STEPS),
            'areas' => ServiceArea::query()
                ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $inspection->service_area_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'options' => [
                'purpose' => PropertyPurpose::options(),
                'buildingType' => BuildingType::options(),
                'connectionType' => ConnectionType::options(),
                'voltageLevel' => VoltageLevel::options(),
                'protectionType' => ProtectionType::options(),
                'fuseType' => FuseType::options(),
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
            // Per building type, per structure; the form prices it as the contractor fills in.
            'fees' => InspectionFee::table(),
            // Sent back by NSD: the last step resubmits instead of paying.
            'changesRequested' => $inspection->needsChanges() ? [
                'note' => $inspection->review_note,
                'ticketNo' => $inspection->ticket_no,
                'requestedAt' => $inspection->reviewed_at?->format('d M Y'),
            ] : null,
        ]);
    }

    /**
     * Auto-save / Save draft (JSON). Drafts exist on the server from the
     * moment the contractor starts an assigned job.
     */
    public function saveDraft(SaveDraftRequest $request, string $uuid, SaveDraft $save): JsonResponse
    {
        abort_unless(Str::isUuid($uuid), 404);

        $inspection = Inspection::query()->where('uuid', $uuid)->first();

        // Logged so a phone stuck resending a draft can be traced (no values).
        if (! $inspection || Gate::denies('update', $inspection)) {
            Log::warning('Draft save refused', [
                'user' => $request->user()?->id,
                'inspection' => $uuid,
                'status' => $inspection?->status->value,
                'reason' => $inspection ? 'forbidden' : 'not_found',
            ]);

            abort($inspection ? 403 : 404);
        }

        $save->handle($inspection, $request->validated());

        return response()->json([
            'uuid' => $inspection->uuid,
            'saved_at' => $inspection->updated_at?->toIso8601String(),
            'signature_url' => $inspection->signature_path
                ? route('inspections.signature', $inspection).'?v='.md5($inspection->signature_path)
                : null,
        ]);
    }

    /** A report sent back by NSD goes back into the review queue; no new payment. */
    public function resubmit(Request $request, Inspection $inspection, ResubmitInspection $resubmit): RedirectResponse
    {
        Gate::authorize('resubmit', $inspection);

        $resubmit->handle($inspection, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Resubmitted. NSD will review it again.']);

        return to_route('inspections.show', $inspection);
    }

    private function notStarted(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'info', 'message' => 'Start this job from My inspections when you are at the property.']);

        return to_route('inspections.index');
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
