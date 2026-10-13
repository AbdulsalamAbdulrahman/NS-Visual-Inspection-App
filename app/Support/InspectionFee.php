<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\FeeSchedule;
use App\Models\Inspection;

/**
 * What an inspection costs: the fee for its building type (or the default),
 * per structure, times the number of structures.
 */
final class InspectionFee
{
    /**
     * @return array{unitKobo: int, count: int, totalKobo: int, unit: string, amount: string, effectiveFrom: string, breakdown: string|null}|null
     */
    public static function quote(Inspection $inspection): ?array
    {
        $fee = FeeSchedule::forType($inspection->building_type);

        if ($fee === null) {
            return null;
        }

        $count = max(1, (int) $inspection->structure_count);
        $total = $fee->amount_kobo * $count;

        return [
            'unitKobo' => $fee->amount_kobo,
            'count' => $count,
            'totalKobo' => $total,
            'unit' => Money::format($fee->amount_kobo),
            'amount' => Money::format($total),
            'effectiveFrom' => $fee->effective_from->format('d M Y'),
            'breakdown' => $count > 1 ? "{$count} structures × ".Money::format($fee->amount_kobo) : null,
        ];
    }

    /**
     * Fees the form needs to price any building type offline.
     *
     * @return array{default: int|null, types: array<string, int>, effectiveFrom: string|null}
     */
    public static function table(): array
    {
        // Rows in effect, newest first: the first per building type wins.
        $inEffect = FeeSchedule::query()
            ->whereDate('effective_from', '<=', today()->toDateString())
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->get(['building_type', 'amount_kobo', 'effective_from'])
            ->unique(fn (FeeSchedule $fee): string => $fee->building_type->value ?? '');

        $default = $inEffect->first(fn (FeeSchedule $fee): bool => $fee->building_type === null);

        return [
            'default' => $default?->amount_kobo,
            'types' => $inEffect
                ->filter(fn (FeeSchedule $fee): bool => $fee->building_type !== null)
                ->mapWithKeys(fn (FeeSchedule $fee): array => [$fee->building_type->value => $fee->amount_kobo])
                ->all(),
            'effectiveFrom' => $default?->effective_from->format('d M Y'),
        ];
    }
}
