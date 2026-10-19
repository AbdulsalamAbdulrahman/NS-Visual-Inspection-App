<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeScheduleRequest;
use App\Models\FeeSchedule;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AD-10, per the LECAN fee sheet (Oct 2026): a fee per structure for each
 * building type × supply × premises (₦0 = not offered), plus the default as a
 * fallback; one scheduled change per fee; history.
 */
class FeeController extends Controller
{
    /** The sheet's four columns, in order. */
    private const COLUMNS = [
        [ConnectionType::SinglePhase, Premises::StandAlone],
        [ConnectionType::ThreePhase, Premises::StandAlone],
        [ConnectionType::SinglePhase, Premises::Multiple],
        [ConnectionType::ThreePhase, Premises::Multiple],
    ];

    public function index(): Response
    {
        // Every live row, newest first; the first in effect per cell is current.
        $all = FeeSchedule::query()->with('creator:id,name')->orderByDesc('effective_from')->orderByDesc('id')->get();
        $current = $all->reject(fn (FeeSchedule $fee): bool => $fee->isScheduled())->unique(fn (FeeSchedule $fee): string => $fee->cellKey())->keyBy(fn (FeeSchedule $fee): string => $fee->cellKey());
        $scheduled = $all->filter(fn (FeeSchedule $fee): bool => $fee->isScheduled())->sortBy('effective_from')->unique(fn (FeeSchedule $fee): string => $fee->cellKey())->keyBy(fn (FeeSchedule $fee): string => $fee->cellKey());

        $cell = function (?BuildingType $type, ?ConnectionType $connection, ?Premises $premises) use ($current, $scheduled): array {
            $key = FeeSchedule::keyFor($type, $connection, $premises);

            return [
                'key' => $key,
                'buildingType' => $type?->value,
                'connectionType' => $connection?->value,
                'premises' => $premises?->value,
                'label' => collect([$type?->label() ?? 'Default fee', $connection?->label(), $premises?->label()])->filter()->implode(' · '),
                'current' => ($fee = $current->get($key)) ? $this->present($fee) : null,
                'scheduled' => ($next = $scheduled->get($key)) ? $this->present($next) : null,
            ];
        };

        return Inertia::render('admin/Fees', [
            'default' => $cell(null, null, null),
            'columns' => array_map(fn (array $column): array => [
                'connectionType' => $column[0]->label(),
                'premises' => $column[1]->label(),
            ], self::COLUMNS),
            'rows' => array_map(fn (BuildingType $type): array => [
                'key' => $type->value,
                'label' => $type->label(),
                'purpose' => $type->purpose()->label(),
                'cells' => array_map(fn (array $column): array => $cell($type, ...$column), self::COLUMNS),
            ], BuildingType::cases()),
            'history' => $all->take(80)->map(fn (FeeSchedule $fee): array => $this->present($fee))->values(),
        ]);
    }

    public function store(FeeScheduleRequest $request): RedirectResponse
    {
        $fee = FeeSchedule::query()->create([
            'building_type' => BuildingType::tryFrom((string) $request->validated('building_type')),
            'connection_type' => ConnectionType::tryFrom((string) $request->validated('connection_type')),
            'premises' => Premises::tryFrom((string) $request->validated('premises')),
            'amount_kobo' => $request->amountKobo(),
            'effective_from' => $request->validated('effective_from'),
            'reason' => $request->validated('reason'),
            'created_by' => $request->user()->id,
        ]);

        $which = $fee->cellLabel();
        $when = $fee->isScheduled() ? "from {$fee->effective_from->format('d M Y')}" : 'from today';

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $fee->amount_kobo === 0
                ? "{$which}: not offered {$when}."
                : "{$which}: ".Money::format($fee->amount_kobo)." per structure {$when}.",
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
            'buildingTypeLabel' => $fee->cellLabel(),
            'amountKobo' => $fee->amount_kobo,
            'notOffered' => $fee->amount_kobo === 0,
            'amount' => Money::format($fee->amount_kobo),
            'effectiveFrom' => $fee->effective_from->format('d M Y'),
            'changedOn' => $fee->created_at?->format('d M Y'),
            'changedBy' => $fee->creator->name ?? 'System',
            'reason' => $fee->reason,
            'isScheduled' => $fee->isScheduled(),
        ];
    }
}
