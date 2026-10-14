<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Accounts\SendLicenceReminders;
use Illuminate\Console\Command;

class LicenceCheckCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'contractors:licence-check';

    /**
     * @var string
     */
    protected $description = 'Email contractors whose licence expires within a month or has expired (once each)';

    public function handle(SendLicenceReminders $send): int
    {
        $counts = $send->handle();

        $this->info("Reminded {$counts['reminded']}, expired {$counts['expired']}.");

        return self::SUCCESS;
    }
}
