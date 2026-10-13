<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\NemsaCategory;
use App\Enums\NigerianState;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validation for a contractor account, shared by the admin drawer
 * (ContractorRequest) and the CSV bulk registration (ImportContractors).
 */
final class ContractorRules
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalise(array $input): array
    {
        $text = fn (string $key): ?string => filled($input[$key] ?? null) ? trim((string) $input[$key]) : null;

        return [
            ...$input,
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => Str::lower(trim((string) ($input['email'] ?? ''))),
            'phone' => $text('phone'),
            'nemsa_reg_no' => Str::upper(preg_replace('/\s+/', '', (string) ($input['nemsa_reg_no'] ?? '')) ?? ''),
            'coren_no' => $text('coren_no'),
            'firm_name' => $text('firm_name'),
            'engineer_name' => $text('engineer_name'),
            'engineer_reg_no' => $text('engineer_reg_no'),
        ];
    }

    /**
     * @param  array<string, mixed>  $input  normalised input
     * @return array<string, mixed>
     */
    public static function rules(array $input, ?User $contractor = null): array
    {
        $corporate = self::isCorporate($input);

        return [
            'name' => ['required', 'string', 'max:255'],
            // Deleted accounts keep their email reserved (see decisions.md).
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($contractor?->id)],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+ ()-]{7,}$/'],
            'nemsa_category' => ['required', Rule::enum(NemsaCategory::class)],
            'nemsa_reg_no' => [
                'required', 'string', 'max:40',
                // Client feedback: individuals use letters and numbers only; corporate
                // numbers are slash-separated groups like CC/CE1F/PR/08634.
                $corporate ? 'regex:#^[A-Z0-9]+(/[A-Z0-9]+){2,5}$#' : 'regex:/^[A-Z0-9]+$/',
                Rule::unique('contractor_profiles', 'nemsa_reg_no')->ignore($contractor?->contractorProfile?->id),
            ],
            'coren_no' => ['nullable', 'string', 'max:40'],
            'firm_name' => [Rule::requiredIf($corporate), 'nullable', 'string', 'max:255'],
            // Corporate firms name their electrical engineer/technologist.
            'engineer_name' => [Rule::requiredIf($corporate), 'nullable', 'string', 'max:255'],
            'engineer_reg_no' => ['nullable', 'string', 'max:60'],
            // LECAN chapter they work in: reps assign jobs to contractors in the area's state.
            'state' => ['required', Rule::enum(NigerianState::class)],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function messages(array $input): array
    {
        return [
            'email.unique' => 'An account with this email already exists (it may have been deleted).',
            'nemsa_reg_no.regex' => self::isCorporate($input)
                ? 'Use the corporate format, e.g. CC/CE1F/PR/08634.'
                : 'Letters and numbers only, no spaces or slashes.',
            'engineer_name.required' => 'Required for Corporate contractors',
            'nemsa_reg_no.unique' => 'Another contractor already has this registration number.',
            'firm_name.required' => 'Required for Corporate contractors',
            'phone.regex' => 'Enter a valid phone number.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'nemsa_category' => 'NEMSA category',
            'nemsa_reg_no' => 'NEMSA registration no.',
            'coren_no' => 'COREN no.',
            'state' => 'state',
        ];
    }

    /**
     * Validated input in the shape CreateContractor / UpdateContractor expect.
     *
     * @param  array<string, mixed>  $input
     * @return array{name: string, email: string, phone: string|null, nemsa_category: string, nemsa_reg_no: string, coren_no: string|null, firm_name: string|null, engineer_name: string|null, engineer_reg_no: string|null, state: string}
     */
    public static function data(array $input): array
    {
        $optional = fn (string $key): ?string => filled($input[$key] ?? null) ? (string) $input[$key] : null;

        return [
            'name' => (string) $input['name'],
            'email' => (string) $input['email'],
            'phone' => $optional('phone'),
            'nemsa_category' => (string) $input['nemsa_category'],
            'nemsa_reg_no' => (string) $input['nemsa_reg_no'],
            'coren_no' => $optional('coren_no'),
            'firm_name' => $optional('firm_name'),
            'engineer_name' => $optional('engineer_name'),
            'engineer_reg_no' => $optional('engineer_reg_no'),
            'state' => (string) $input['state'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function isCorporate(array $input): bool
    {
        return ($input['nemsa_category'] ?? null) === NemsaCategory::Corporate->value;
    }
}
