<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\FeeScheduleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Writes the official fee sheet as new fee rows, from the given day. Older rows
 * stay as history. Run once on a live database.
 */
class LoadOfficialFees extends Command
{
    protected $signature = 'fees:load-official {--from= : First day the fees apply (Y-m-d); today by default}';

    protected $description = 'Load the official fee sheet into fee settings';

    public function handle(): int
    {
        $from = $this->option('from') ? Carbon::parse((string) $this->option('from')) : today();

        FeeScheduleSeeder::seedSheet($from);

        $this->info('Official fees loaded, effective '.$from->toDateString().'.');

        return self::SUCCESS;
    }
}
