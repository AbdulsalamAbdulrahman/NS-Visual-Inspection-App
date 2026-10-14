<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Inspection;

/**
 * Receipt actions shown with a paid report: print it, or email it to the owner.
 */
class ReceiptLinks
{
    /**
     * @return array{printUrl: string, sendUrl: string, ownerEmail: string|null}|null
     */
    public static function for(Inspection $inspection): ?array
    {
        if (! $inspection->isSubmitted() || ! $inspection->paidPayment()->exists()) {
            return null;
        }

        return [
            'printUrl' => route('inspections.receipt', $inspection),
            'sendUrl' => route('inspections.receipt.send', $inspection),
            'ownerEmail' => $inspection->owner_email,
        ];
    }
}
