<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\NigerianState;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LecanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $official */
        $official = $this->route('official');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($official?->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+ ()-]{7,}$/'],
            'lecan_state' => ['required', Rule::enum(NigerianState::class)],
        ];
    }

    /**
     * @return array{name: string, email: string, phone: string|null, lecan_state: string}
     */
    public function officialData(): array
    {
        $input = $this->safe();

        return [
            'name' => $input->string('name')->toString(),
            'email' => $input->string('email')->toString(),
            'phone' => $input->filled('phone') ? $input->string('phone')->toString() : null,
            'lecan_state' => $input->string('lecan_state')->toString(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists (it may have been deleted).',
            'lecan_state.required' => 'Choose the state chapter they review for.',
            'phone.regex' => 'Enter a valid phone number.',
        ];
    }
}
