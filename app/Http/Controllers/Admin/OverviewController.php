<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionRowResource;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\ServiceArea;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AD-01 / AM-01: KPIs for a month, submissions by area, recent submissions.
 */
class OverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $month = $this->month($request->string('month')->toString());
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $previous = $start->copy()->subMonthNoOverflow();

        $submittedIn = fn (CarbonImmutable $from, CarbonImmutable $to): int => Inspection::query()->submitted()
            ->whereBetween('submitted_at', [$from, $to])->count();

        $thisMonth = $submittedIn($start, $end);
        $lastMonth = $submittedIn($previous->copy()->startOfMonth(), $previous->copy()->endOfMonth());

        $revenueKobo = (int) Payment::query()->where('status', PaymentStatus::Paid)
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount_paid_kobo');

        $contractors = User::query()->role(Role::Contractor)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $byArea = Inspection::query()->submitted()
            ->whereBetween('submitted_at', [$start, $end])
            ->selectRaw('service_area_id, count(*) as total')
            ->groupBy('service_area_id')
            ->pluck('total', 'service_area_id');

        $areas = ServiceArea::query()->orderBy('name')->get(['id', 'name', 'is_active'])
            ->filter(fn (ServiceArea $a) => $a->is_active || isset($byArea[$a->id]))
            ->map(fn (ServiceArea $a): array => ['name' => $a->name, 'count' => (int) ($byArea[$a->id] ?? 0)])
            ->sortByDesc('count')
            ->values();

        $recent = Inspection::query()->submitted()
            ->with(['serviceArea:id,name', 'contractor:id,name'])
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        return Inertia::render('admin/Overview', [
            'month' => [
                'value' => $start->format('Y-m'),
                'label' => $start->format('F Y'),
                'short' => $start->format('F'),
                'isCurrent' => $start->isSameMonth(now()),
                'previousShort' => $previous->format('M'),
                'options' => collect(range(0, 11))->map(fn (int $i): array => [
                    'value' => now()->startOfMonth()->subMonthsNoOverflow($i)->format('Y-m'),
                    'label' => now()->startOfMonth()->subMonthsNoOverflow($i)->format('F Y'),
                ]),
            ],
            'kpis' => [
                'inspections' => $thisMonth,
                'inspectionsDelta' => $thisMonth - $lastMonth,
                'revenueKobo' => $revenueKobo,
                'revenue' => Money::format($revenueKobo),
                'revenueShort' => $this->compactNaira($revenueKobo),
                'contractorsActive' => (int) ($contractors[UserStatus::Active->value] ?? 0),
                'contractorsTotal' => (int) $contractors->sum(),
                'contractorsSuspended' => (int) ($contractors[UserStatus::Suspended->value] ?? 0),
                'drafts' => Inspection::query()->drafts()->count(),
            ],
            'areas' => $areas,
            'recent' => InspectionRowResource::collection($recent),
        ]);
    }

    private function month(string $value): CarbonImmutable
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value)) {
            $month = Date::createFromFormat('Y-m-d', "{$value}-01")?->startOfDay();

            if ($month?->lte(now())) {
                return $month;
            }
        }

        return now()->startOfMonth();
    }

    /** ₦1,305,000 → "₦1.31M", ₦45,000 → "₦45K" (AD-01 tile). */
    private function compactNaira(int $kobo): string
    {
        $naira = $kobo / 100;

        return match (true) {
            $naira >= 1_000_000 => '₦'.rtrim(rtrim(number_format($naira / 1_000_000, 2), '0'), '.').'M',
            $naira >= 1_000 => '₦'.rtrim(rtrim(number_format($naira / 1_000, 1), '0'), '.').'K',
            default => '₦'.number_format($naira),
        };
    }
}
