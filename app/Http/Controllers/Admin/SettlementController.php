<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\RegisterSettlementAccount;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccountRequest;
use App\Models\PaymentSplit;
use App\Models\SettlementParty;
use App\Services\Monnify\MonnifyClient;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Settlement portal: ratios, accounts, and totals per party. Monnify moves the money.
 */
class SettlementController extends Controller
{
    public function index(Request $request, MonnifyClient $monnify): Response
    {
        [$from, $to] = $this->period($request);
        $totals = $this->splits($from, $to)->selectRaw('party, sum(amount_kobo) as total')->groupBy('party')->pluck('total', 'party');

        return Inertia::render('admin/Settlements', [
            'parties' => SettlementParty::query()->orderBy('id')->get()->map(fn (SettlementParty $p): array => [
                'id' => $p->id,
                'label' => $p->key->label(),
                'percentage' => $p->percentage_bp / 100,
                'hasOwnAccount' => $p->hasOwnAccount(),
                'bankName' => $p->bank_name,
                'accountNumber' => $p->account_number,
                'accountName' => $p->account_name,
                'ready' => filled($p->sub_account_code),
                'total' => Money::format((int) ($totals[$p->key->value] ?? 0)),
            ])->all(),
            'banks' => $monnify->banksOrNone(),
            'filters' => ['from' => $from, 'to' => $to],
            'splitEnabled' => (bool) config('services.monnify.split_enabled'),
        ]);
    }

    public function ratios(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $parties = SettlementParty::query()->get();
        $points = fn (SettlementParty $p): int => (int) round(((float) ($data['percentages'][$p->id] ?? -1)) * 100);

        if ($parties->contains(fn (SettlementParty $p): bool => $points($p) < 0) || $parties->sum($points) !== 10_000) {
            return back()->withErrors(['percentages' => 'The percentages must add up to exactly 100%.']);
        }

        $parties->each(fn (SettlementParty $p) => $p->update(['percentage_bp' => $points($p)]));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ratios saved. They apply to new payments.']);

        return back();
    }

    public function account(BankAccountRequest $request, SettlementParty $party, RegisterSettlementAccount $register): RedirectResponse
    {
        abort_unless($party->hasOwnAccount(), 404);

        $party->update($register->handle(
            $request->string('bank_code')->toString(),
            $request->string('account_number')->toString(),
            (string) config('kens.nsd.email'),
        ));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Saved: {$party->account_name}."]);

        return back();
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $query = $this->splits($from, $to)->with('payment.inspection:id,ticket_no');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                throw new RuntimeException('Could not open the CSV output stream.');
            }

            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Paid on', 'Ticket', 'Party', 'Percent', 'Amount']);

            foreach ($query->lazyById(500) as $s) {
                fputcsv($out, [$s->payment->paid_at?->format('Y-m-d'), $s->payment->inspection->ticket_no, $s->party->label(), $s->percentage_bp / 100, Money::format($s->amount_kobo)]);
            }

            fclose($out);
        }, "kens-settlements-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: string, 1: string} Y-m-d; this month by default
     */
    private function period(Request $request): array
    {
        $date = function (string $key, string $default) use ($request): string {
            $value = $request->string($key)->toString();

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : $default;
        };

        return [$date('from', now()->startOfMonth()->toDateString()), $date('to', now()->toDateString())];
    }

    /**
     * @return Builder<PaymentSplit>
     */
    private function splits(string $from, string $to): Builder
    {
        return PaymentSplit::query()->whereHas('payment', fn (Builder $q) => $q
            ->where('status', PaymentStatus::Paid)
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to));
    }
}
