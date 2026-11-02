<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bank details for a settlement account (inspector or party).
 */
class BankAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bank_code' => ['required', 'string', 'max:20'],
            'account_number' => ['required', 'digits:10'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['account_number.digits' => 'Enter the 10-digit NUBAN account number.'];
    }
}
