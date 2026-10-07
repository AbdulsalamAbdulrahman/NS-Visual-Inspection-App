<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FeeSchedule;
use Illuminate\Database\Seeder;

/**
 * Launch fee: ₦15,000 from the day the app is first seeded. Admins change
 * it later on the Fee settings page.
 */
class FeeScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (FeeSchedule::withTrashed()->exists()) {
            return;
        }

        FeeSchedule::query()->create([
            'amount_kobo' => 1_500_000,
            'effective_from' => today(),
            'reason' => 'Launch fee',
        ]);
    }
}
