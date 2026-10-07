<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Inspection;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inspection_id' => Inspection::factory()->complete(),
            'contractor_id' => fn (array $attributes) => Inspection::query()->whereKey($attributes['inspection_id'])->value('contractor_id'),
            'payment_reference' => 'KENS-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'amount_kobo' => 1_500_000,
            'status' => PaymentStatus::Pending,
        ];
    }

    public function forInspection(Inspection $inspection): static
    {
        return $this->state([
            'inspection_id' => $inspection->id,
            'contractor_id' => $inspection->contractor_id,
        ]);
    }
}
