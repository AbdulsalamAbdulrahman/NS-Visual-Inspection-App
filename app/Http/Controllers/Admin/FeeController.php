<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BuildingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeScheduleRequest;
use App\Models\FeeSchedule;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AD-10, per building type (client feedback): a default fee plus optional fees
 * per building type, all per structure; one scheduled change per fee; history.
 */
class FeeController extends Controller
{
    public function index(): Response
    {
        $scheduled = FeeSchedule::query()->scheduled()->orderBy('effective_from')->get()
            ->keyBy(fn (FeeSchedule $fee): string => $fee->building_type->value ?? 'default');

        $default = FeeSchedule::current();

        // One row for the default fee, then one per building type (own fee or "uses default").
        $rows = collect([null, ...BuildingType::cases()])->map(function (?BuildingType $type) use ($scheduled, $default): array {
            $own = $type ? FeeSchedule::query()->where('building_type', $type)
                ->whereDate('effective_from', '<=', today()->toDateString())
                ->orderByDesc('effective_from')->orderByDesc('id')->first() : $default;
            $next = $scheduled->get($type->value ?? 'default');

            return [
                'key' => $type->value ?? 'default',
                'label' => $type?->label() ?? 'Default fee',
                'purpose' => $type?->purpose()->label(),
                'current' => $own ? $this->present($own) : null,
                'usesDefault' => $type !== null && $own === null,
                'scheduled' => $next ? $this->present($next) : null,
            ];
        });

        $history = FeeSchedule::query()
            ->with('creator:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(80)
            ->get();

        return Inertia::render('admin/Fees', [
            'default' => $default ? $this->present($default) : null,
            'rows' => $rows,
            'history' => $history->map(fn (FeeSchedule $fee): array => $this->present($fee)),
        ]);
    }

    public function store(FeeScheduleRequest $request): RedirectResponse
    {
        $type = BuildingType::tryFrom((string) $request->validated('building_type'));

        $fee = FeeSchedule::query()->create([
            'building_type' => $type,
            'amount_kobo' => $request->amountKobo(),
            'effective_from' => $request->validated('effective_from'),
            'reason' => $request->validated('reason'),
            'created_by' => $request->user()->id,
        ]);

        $amount = Money::format($fee->amount_kobo);
        $which = $type ? "{$type->label()} fee" : 'default fee';

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $fee->isScheduled()
                ? "{$amount} {$which} scheduled from {$fee->effective_from->format('d M Y')}."
                : "{$amount} is now the {$which} (per structure).",
        ]);

        return back();
    }

    public function destroy(Request $request, FeeSchedule $fee): RedirectResponse
    {
        abort_unless($fee->isScheduled(), 422, 'Only a scheduled change can be cancelled.');

        $fee->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Scheduled fee change cancelled.']);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(FeeSchedule $fee): array
    {
        return [
            'id' => $fee->id,
            'buildingType' => $fee->building_type?->value,
            'buildingTypeLabel' => $fee->building_type?->label() ?? 'Default',
            'amountKobo' => $fee->amount_kobo,
            'amount' => Money::format($fee->amount_kobo),
            'effectiveFrom' => $fee->effective_from->format('d M Y'),
            'changedOn' => $fee->created_at?->format('d M Y'),
            'changedBy' => $fee->creator->name ?? 'System',
            'reason' => $fee->reason,
            'isScheduled' => $fee->isScheduled(),
        ];
    }
}
