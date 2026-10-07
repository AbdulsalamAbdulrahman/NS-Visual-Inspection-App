<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\FeeSchedule;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class FeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $kobo = Money::parseNaira(is_scalar($value) ? (string) $value : null);

                    if ($kobo === null) {
                        $fail('Enter an amount in naira, e.g. 17,500.');
                    } elseif ($kobo < 100) {
                        $fail('The fee must be at least ₦1.');
                    } elseif ($kobo > 100_000_000) {
                        $fail('That amount is too large.');
                    }
                },
            ],
            'effective_from' => [
                'required', 'date_format:Y-m-d', 'after_or_equal:today',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (FeeSchedule::query()->scheduled()->exists()) {
                        $fail('A fee change is already scheduled. Cancel it before scheduling another.');
                    }
                },
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function amountKobo(): int
    {
        return (int) Money::parseNaira((string) $this->validated('amount'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'effective_from.after_or_equal' => 'The effective date can’t be in the past.',
        ];
    }
}
