<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Concerns\PasswordValidationRules;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class FirstPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return (bool) $this->user()?->must_change_password;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => [
                ...$this->passwordRules(),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && Hash::check($value, (string) $this->user()?->password)) {
                        $fail('Choose a password different from the temporary one in your email.');
                    }
                },
            ],
        ];
    }
}
