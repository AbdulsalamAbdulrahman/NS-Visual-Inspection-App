<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Enums\PropertyPurpose;
use App\Models\FeeSchedule;

/**
 * The official fees in force today, for the public home page: per building
 * type and size band, single and three phase, offered cells only.
 */
final class PublicFees
{
    /**
     * @return list<array{purpose: string, rows: list<array{building: string, size: string, single: string|null, three: string|null}>}>
     */
    public static function groups(): array
    {
        // One query: newest first, so the first row in effect per cell is current.
        $current = FeeSchedule::query()
            ->whereDate('effective_from', '<=', today())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (FeeSchedule $fee): string => $fee->cellKey())
            ->keyBy(fn (FeeSchedule $fee): string => $fee->cellKey());

        $amount = function (BuildingType $type, ConnectionType $connection, Premises $premises) use ($current): ?string {
            $kobo = $current->get(FeeSchedule::keyFor($type, $connection, $premises))?->amount_kobo;

            return $kobo ? Money::format($kobo) : null;
        };

        $groups = [];

        foreach (BuildingType::cases() as $type) {
            foreach (Premises::cases() as $premises) {
                $single = $amount($type, ConnectionType::SinglePhase, $premises);
                $three = $amount($type, ConnectionType::ThreePhase, $premises);

                if ($single === null && $three === null) {
                    continue;
                }

                $groups[$type->purpose()->value][] = [
                    'building' => $type->label(),
                    'size' => self::sizeLabel($type, $premises),
                    'single' => $single,
                    'three' => $three,
                ];
            }
        }

        return array_values(array_filter(array_map(
            fn (PropertyPurpose $purpose): ?array => isset($groups[$purpose->value])
                ? ['purpose' => $purpose->label(), 'rows' => $groups[$purpose->value]]
                : null,
            PropertyPurpose::cases(),
        )));
    }

    /** The size band in words, matching Premises::forBuilding(). */
    private static function sizeLabel(BuildingType $type, Premises $premises): string
    {
        return match ($type) {
            BuildingType::BlockOfFlats, BuildingType::TwoBedroomFlat, BuildingType::ThreeBedroomFlat => match ($premises) {
                Premises::StandAlone => '1–4 flats',
                Premises::Multiple => '5–15 flats',
                Premises::Large => 'Above 15 flats',
            },
            BuildingType::Shop => $premises === Premises::StandAlone ? '1–5 shops' : 'Above 5 shops',
            BuildingType::Plaza, BuildingType::LargePlaza => 'Per shop',
            default => $premises === Premises::StandAlone ? 'One building' : 'Two or more buildings',
        };
    }
}
