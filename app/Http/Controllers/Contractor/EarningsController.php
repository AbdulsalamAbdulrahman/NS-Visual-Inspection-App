<?php

declare(strict_types=1);

namespace App\Http\Controllers\Contractor;

use App\Enums\PaymentStatus;
use App\Enums\SettlementParty;
use App\Http\Controllers\Controller;
use App\Models\PaymentSplit;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The inspector's own share of each paid fee.
 */
class EarningsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = PaymentSplit::query()
            ->where('party', SettlementParty::Inspector)
            ->where('contractor_id', $request->user()->id)
            ->whereHas('payment', fn (Builder $q) => $q->where('status', PaymentStatus::Paid));

        $rows = (clone $query)->with('payment.inspection:id,ticket_no,owner_name')->latest('id')->limit(100)->get();

        return Inertia::render('contractor/Earnings', [
            'total' => Money::format((int) $query->sum('amount_kobo')),
            'rows' => $rows->map(fn (PaymentSplit $s): array => [
                'id' => $s->id,
                'ticketNo' => $s->payment->inspection->ticket_no,
                'owner' => $s->payment->inspection->owner_name,
                'paidAt' => $s->payment->paid_at?->format('d M Y'),
                'amount' => Money::format($s->amount_kobo),
            ])->all(),
        ]);
    }
}
