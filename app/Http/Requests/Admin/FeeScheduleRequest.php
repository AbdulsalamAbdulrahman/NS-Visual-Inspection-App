<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\BuildingType;
use App\Enums\ConnectionType;
use App\Enums\Premises;
use App\Models\FeeSchedule;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $type = BuildingType::tryFrom($this->string('building_type')->toString());
        $connection = ConnectionType::tryFrom($this->string('connection_type')->toString());
        $premises = Premises::tryFrom($this->string('premises')->toString());

        return [
            // Empty = the default fee, for building types without their own.
            'building_type' => ['nullable', Rule::enum(BuildingType::class)],
            // A cell of the fee sheet: supply and premises together, for a building type.
            'connection_type' => ['nullable', 'required_with:premises', 'prohibited_if:building_type,null', Rule::enum(ConnectionType::class)],
            'premises' => ['nullable', 'required_with:connection_type', Rule::enum(Premises::class)],
            'not_offered' => [
                'sometimes', 'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($connection): void {
                    if ($value && $connection === null) {
                        $fail('Only a building type with a supply and premises can be marked not offered.');
                    }
                },
            ],
            'amount' => [
                'exclude_if:not_offered,true',
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
                function (string $attribute, mixed $value, Closure $fail) use ($type, $connection, $premises): void {
                    $cell = FeeSchedule::query()->scheduled()
                        ->where('building_type', $type)
                        ->where('connection_type', $connection)
                        ->where('premises', $premises);

                    if ($cell->exists()) {
                        $fail('A change to this fee is already scheduled. Cancel it before scheduling another.');
                    }
                },
            ],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** 0 = not offered for this cell. */
    public function amountKobo(): int
    {
        return $this->boolean('not_offered') ? 0 : (int) Money::parseNaira((string) $this->validated('amount'));
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
