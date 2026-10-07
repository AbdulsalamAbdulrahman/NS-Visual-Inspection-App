<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentRowResource;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AD-09: every Monnify attempt with status filter, search and CSV export.
 */
class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = PaymentStatus::tryFrom($request->string('status')->toString());
        $search = trim($request->string('search')->toString());

        $payments = $this->query($status, $search)
            ->with(['contractor:id,name', 'inspection:id,uuid,ticket_no'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $start = now()->startOfMonth();
        $month = Payment::query()->where('created_at', '>=', $start)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('admin/Payments', [
            'payments' => PaymentRowResource::collection($payments),
            'filters' => ['status' => $status?->value, 'search' => $search],
            'summary' => [
                'month' => $start->format('F'),
                'collected' => Money::format((int) Payment::query()->where('status', PaymentStatus::Paid)->where('paid_at', '>=', $start)->sum('amount_paid_kobo')),
                'successful' => (int) ($month[PaymentStatus::Paid->value] ?? 0),
                'failed' => (int) ($month[PaymentStatus::Failed->value] ?? 0),
                'abandoned' => (int) ($month[PaymentStatus::Abandoned->value] ?? 0),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query(
            PaymentStatus::tryFrom($request->string('status')->toString()),
            trim($request->string('search')->toString()),
        )->with(['contractor:id,name', 'inspection:id,uuid,ticket_no']);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Monnify reference', 'Our reference', 'Ticket', 'Contractor', 'Amount', 'Amount paid', 'Channel', 'Status', 'Started', 'Paid at']);

            foreach ($query->lazyByIdDesc(500) as $p) {
                fputcsv($out, [
                    $p->transaction_reference,
                    $p->payment_reference,
                    $p->inspection?->ticket_no,
                    $p->contractor?->name,
                    Money::format($p->amount_kobo),
                    $p->amount_paid_kobo !== null ? Money::format($p->amount_paid_kobo) : null,
                    $p->channelLabel(),
                    $p->status->label(),
                    $p->created_at?->format('Y-m-d H:i'),
                    $p->paid_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, 'kens-payments-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return Builder<Payment>
     */
    private function query(?PaymentStatus $status, string $search): Builder
    {
        return Payment::query()
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('payment_reference', 'like', $like)
                    ->orWhere('transaction_reference', 'like', $like)
                    ->orWhereHas('inspection', fn (Builder $i) => $i->where('ticket_no', 'like', $like))
                    ->orWhereHas('contractor', fn (Builder $c) => $c->where('name', 'like', $like)));
            });
    }
}
