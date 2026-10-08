<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payments\AbandonStalePayments;
use Illuminate\Console\Command;

class AbandonStalePaymentsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'payments:abandon-stale {--hours=24 : Pending longer than this many hours}';

    /**
     * @var string
     */
    protected $description = 'Mark payments still pending after 24 hours as abandoned (after a last check with Monnify)';

    public function handle(AbandonStalePayments $abandon): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $counts = $abandon->handle($hours);

        $this->info(sprintf(
            'Abandoned %d, found paid %d, failed %d, skipped %d (Monnify unavailable).',
            $counts['abandoned'],
            $counts['paid'],
            $counts['failed'],
            $counts['skipped'],
        ));

        return self::SUCCESS;
    }
}
