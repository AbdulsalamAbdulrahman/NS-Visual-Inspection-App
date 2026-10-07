<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RepRequest extends FormRequest
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
            'service_area_ids' => array_values(array_unique(array_map('intval', (array) $this->input('service_area_ids', [])))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $rep */
        $rep = $this->route('rep');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($rep?->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+ ()-]{7,}$/'],
            'service_area_ids' => ['required', 'array', 'min:1', 'max:3'],
            'service_area_ids.*' => ['integer', Rule::exists('service_areas', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists (it may have been deleted).',
            'service_area_ids.required' => 'Assign at least one service area.',
            'service_area_ids.min' => 'Assign at least one service area.',
            'service_area_ids.max' => 'A rep can have at most 3 service areas.',
            'service_area_ids.*.exists' => 'Choose active service areas only.',
            'phone.regex' => 'Enter a valid phone number.',
        ];
    }
}
