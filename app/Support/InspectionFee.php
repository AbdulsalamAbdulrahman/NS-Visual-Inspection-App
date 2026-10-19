<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Premises;
use App\Models\FeeSchedule;
use App\Models\Inspection;

/**
 * What an inspection costs: the fee sheet's cell for its building type, supply
 * and premises (else the type's own fee, else the default), per structure,
 * times the number of structures. A ₦0 cell is not offered.
 */
final class InspectionFee
{
    /**
     * @return array{unitKobo: int, count: int, totalKobo: int, unit: string, amount: string, effectiveFrom: string, breakdown: string|null}|null
     */
    public static function quote(Inspection $inspection): ?array
    {
        $count = max(1, (int) $inspection->structure_count);
        $fee = FeeSchedule::forCell($inspection->building_type, $inspection->connection_type, Premises::forStructures($count));

        if ($fee === null || $fee->amount_kobo === 0) {
            return null;
        }

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

    /** Why there is no quote, for the contractor. */
    public static function unavailableMessage(Inspection $inspection): string
    {
        $count = max(1, (int) $inspection->structure_count);
        $fee = FeeSchedule::forCell($inspection->building_type, $inspection->connection_type, Premises::forStructures($count));

        if ($fee !== null && $fee->amount_kobo === 0 && $inspection->building_type && $inspection->connection_type) {
            $premises = $count > 1 ? 'on multiple premises' : 'stand-alone';

            return "No fee is set for a {$inspection->connection_type->label()} {$inspection->building_type->label()} ({$premises}). Contact the New Service Department.";
        }

        return 'The inspection fee has not been set yet. Contact the New Service Department.';
    }

    /**
     * Fees the form needs to price any building offline. Cells are keyed
     * "type|supply|premises"; 0 = not offered.
     *
     * @return array{default: int|null, types: array<string, int>, cells: array<string, int>, effectiveFrom: string|null}
     */
    public static function table(): array
    {
        // Rows in effect, newest first: the first per cell wins.
        $inEffect = FeeSchedule::query()
            ->whereDate('effective_from', '<=', today()->toDateString())
            ->orderByDesc('effective_from')->orderByDesc('id')
            ->get(['building_type', 'connection_type', 'premises', 'amount_kobo', 'effective_from'])
            ->unique(fn (FeeSchedule $fee): string => $fee->cellKey());

        $default = $inEffect->first(fn (FeeSchedule $fee): bool => $fee->building_type === null);
        $amounts = fn (bool $cells): array => $inEffect
            ->filter(fn (FeeSchedule $fee): bool => $fee->building_type !== null && ($fee->connection_type !== null) === $cells)
            ->mapWithKeys(fn (FeeSchedule $fee): array => [$fee->cellKey() => $fee->amount_kobo])
            ->all();

        return [
            'default' => $default?->amount_kobo,
            'types' => $amounts(false),
            'cells' => $amounts(true),
            'effectiveFrom' => $default?->effective_from->format('d M Y'),
        ];
    }
}
