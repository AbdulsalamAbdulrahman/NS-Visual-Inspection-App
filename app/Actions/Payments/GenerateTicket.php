<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Models\TicketCounter;
use Illuminate\Support\Facades\DB;
use LogicException;

class GenerateTicket
{
    /**
     * Next ticket for the current year, e.g. KE-NSD-2026-000123. Must run
     * inside the finalisation transaction: the year's counter row is locked
     * until commit, so concurrent payments queue up and never share a number.
     */
    public function handle(): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Tickets must be generated inside a database transaction.');
        }

        $year = (int) now()->format('Y');

        // Lock the year's row first. Only when it doesn't exist yet (first
        // ticket of the year) insert it — an insert on an existing row would
        // take a shared lock and deadlock against the FOR UPDATE below.
        $counter = TicketCounter::query()->whereKey($year)->lockForUpdate()->first();

        if ($counter === null) {
            TicketCounter::query()->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = TicketCounter::query()->whereKey($year)->lockForUpdate()->firstOrFail();
        }
        $counter->last_number++;
        $counter->save();

        return sprintf('KE-NSD-%d-%06d', $year, $counter->last_number);
    }
}
