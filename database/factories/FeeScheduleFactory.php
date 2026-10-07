<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeeSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeeSchedule>
 */
class FeeScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount_kobo' => 1_500_000,
            'effective_from' => today()->subMonth(),
            'reason' => null,
        ];
    }
}
