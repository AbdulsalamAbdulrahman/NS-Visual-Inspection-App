<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SettlementParty as Party;
use App\Models\SettlementParty;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Splits an inspection fee by the settlement ratios. Shares round down to the
 * kobo and the remainder stays with Admin & KE HQ (the main account).
 */
final class Settlement
{
    /**
     * @return list<array{party: Party, contractor_id: int|null, percentage_bp: int, amount_kobo: int, sub_account_code: string|null}>
     */
    public static function split(int $feeKobo, User $inspector): array
    {
        $rows = [];

        foreach (SettlementParty::query()->orderBy('id')->get() as $party) {
            $isInspector = $party->key === Party::Inspector;

            $rows[] = [
                'party' => $party->key,
                'contractor_id' => $isInspector ? $inspector->id : null,
                'percentage_bp' => $party->percentage_bp,
                'amount_kobo' => intdiv($feeKobo * $party->percentage_bp, 10_000),
                'sub_account_code' => $isInspector ? $inspector->contractorProfile?->sub_account_code : $party->sub_account_code,
            ];
        }

        $remainder = $feeKobo - array_sum(array_column($rows, 'amount_kobo'));

        return array_map(function (array $row) use ($remainder): array {
            if ($row['party'] === Party::AdminHq) {
                $row['amount_kobo'] += $remainder;
            }

            return $row;
        }, $rows);
    }

    /**
     * Monnify's incomeSplitConfig. The main account takes the rest, including
     * the checkout charge, and bears Monnify's fee.
     *
     * @param  list<array{party: Party, contractor_id: int|null, percentage_bp: int, amount_kobo: int, sub_account_code: string|null}>  $rows
     * @return list<array{subAccountCode: string, feePercentage: int, feeBearer: bool, splitAmount: float}>
     *
     * @throws ValidationException when a party has no sub-account yet
     */
    public static function monnifyConfig(array $rows): array
    {
        $config = [];

        foreach ($rows as $row) {
            if ($row['party'] === Party::AdminHq || $row['amount_kobo'] === 0) {
                continue;
            }

            if (blank($row['sub_account_code'])) {
                throw ValidationException::withMessages(['inspection' => "Payment can't start: no settlement account for {$row['party']->label()}. Contact the New Service Department."]);
            }

            $config[] = [
                'subAccountCode' => (string) $row['sub_account_code'],
                'feePercentage' => 0,
                'feeBearer' => false,
                'splitAmount' => round($row['amount_kobo'] / 100, 2),
            ];
        }

        return $config;
    }
}
