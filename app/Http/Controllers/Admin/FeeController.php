<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FeeScheduleRequest;
use App\Models\FeeSchedule;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AD-10: current fee, one scheduled change at a time, history.
 */
class FeeController extends Controller
{
    public function index(): Response
    {
        $current = FeeSchedule::current();
        $scheduled = FeeSchedule::query()->scheduled()->orderBy('effective_from')->first();

        $history = FeeSchedule::query()
            ->with('creator:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('admin/Fees', [
            'current' => $current ? $this->present($current) : null,
            'scheduled' => $scheduled ? $this->present($scheduled) : null,
            'history' => $history->map(fn (FeeSchedule $fee): array => $this->present($fee)),
        ]);
    }

    public function store(FeeScheduleRequest $request): RedirectResponse
    {
        $fee = FeeSchedule::query()->create([
            'amount_kobo' => $request->amountKobo(),
            'effective_from' => $request->validated('effective_from'),
            'reason' => $request->validated('reason'),
            'created_by' => $request->user()->id,
        ]);

        $amount = Money::format($fee->amount_kobo);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $fee->isScheduled()
                ? "{$amount} scheduled from {$fee->effective_from->format('d M Y')}."
                : "{$amount} is now the inspection fee.",
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
