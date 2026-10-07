<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\NemsaCategory;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContractorRequest extends FormRequest
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
            'nemsa_reg_no' => Str::upper(preg_replace('/\s+/', '', (string) $this->input('nemsa_reg_no'))),
            'coren_no' => $this->filled('coren_no') ? trim((string) $this->input('coren_no')) : null,
            'firm_name' => $this->filled('firm_name') ? trim((string) $this->input('firm_name')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User|null $contractor */
        $contractor = $this->route('contractor');

        return [
            'name' => ['required', 'string', 'max:255'],
            // Deleted accounts keep their email reserved (see decisions.md).
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($contractor?->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+ ()-]{7,}$/'],
            'nemsa_category' => ['required', Rule::enum(NemsaCategory::class)],
            'nemsa_reg_no' => [
                'required', 'string', 'max:40', 'regex:#^NEMSA/[A-Z]{1,4}/\d{4}/\d{1,6}$#',
                Rule::unique('contractor_profiles', 'nemsa_reg_no')->ignore($contractor?->contractorProfile?->id),
            ],
            'coren_no' => ['nullable', 'string', 'max:40'],
            'firm_name' => [
                Rule::requiredIf($this->input('nemsa_category') === NemsaCategory::Corporate->value),
                'nullable', 'string', 'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'An account with this email already exists (it may have been deleted).',
            'nemsa_reg_no.regex' => 'Use the format NEMSA/A/2023/0142.',
            'nemsa_reg_no.unique' => 'Another contractor already has this registration number.',
            'firm_name.required' => 'Required for Corporate contractors',
            'phone.regex' => 'Enter a valid phone number.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nemsa_category' => 'NEMSA category',
            'nemsa_reg_no' => 'NEMSA registration no.',
            'coren_no' => 'COREN no.',
        ];
    }
}
