<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\BuildingType;
use App\Enums\PropertyPurpose;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Validation for a job a rep creates (form and CSV import share it): the
 * customer's section A details, one of the rep's area offices, and an active
 * contractor working in that area's state.
 */
final class JobRules
{
    /**
     * Trim the input and put the phone in 11-digit local form (0803…).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalise(array $input): array
    {
        $text = fn (string $key): ?string => filled($input[$key] ?? null) ? trim((string) $input[$key]) : null;

        $phone = preg_replace('/\D+/', '', (string) ($input['owner_phone'] ?? ''));

        if (str_starts_with($phone, '234') && strlen($phone) === 13) {
            $phone = '0'.substr($phone, 3);
        }

        return [
            ...$input,
            'owner_name' => $text('owner_name'),
            'property_address' => $text('property_address'),
            'owner_phone' => $phone === '' ? null : $phone,
            'owner_email' => ($email = $text('owner_email')) === null ? null : Str::lower($email),
            'form74_no' => ($first = $text('form74_no')) === null ? null : Str::upper($first),
            'form74_last_no' => ($last = $text('form74_last_no')) === null ? null : Str::upper($last),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(User $rep): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'property_address' => ['required', 'string', 'max:1000'],
            'owner_phone' => ['required', 'digits:11'],
            'owner_email' => ['nullable', 'email', 'max:255'],
            'service_area_id' => ['required', 'integer', Rule::in($rep->serviceAreas()->pluck('service_areas.id')->all())],
            'purpose' => ['required', Rule::enum(PropertyPurpose::class)],
            'building_type' => ['required', Rule::enum(BuildingType::class)],
            'form74_no' => ['required', 'string', 'max:60'],
            'form74_last_no' => ['nullable', 'string', 'max:60'],
            'contractor' => ['required', 'string'],
        ];
    }

    /**
     * Checks that need several fields at once.
     *
     * @param  array<string, mixed>  $data  input that passed rules()
     * @return array<string, string> field => message
     */
    public static function crossCheck(array $data): array
    {
        $errors = [];

        $type = BuildingType::tryFrom((string) ($data['building_type'] ?? ''));

        if ($type !== null && $type->purpose()->value !== ($data['purpose'] ?? null)) {
            $errors['building_type'] = 'Choose a building description under '.(PropertyPurpose::tryFrom((string) $data['purpose'])?->label() ?? 'the purpose').'.';
        }

        if (filled($data['form74_no'] ?? null) && is_string($count = Form74Range::count($data['form74_no'], $data['form74_last_no'] ?? null))) {
            $errors['form74_last_no'] = $count;
        }

        $area = filled($data['service_area_id'] ?? null) ? ServiceArea::query()->whereKey($data['service_area_id'])->first() : null;

        if ($area !== null && filled($data['contractor'] ?? null) && self::contractor((string) $data['contractor'], $area) === null) {
            $errors['contractor'] = "Choose an active contractor who works in {$area->state->label()} State.";
        }

        return $errors;
    }

    /**
     * An active contractor (by uuid or email) working in the area's state.
     */
    public static function contractor(string $key, ServiceArea $area): ?User
    {
        return User::query()
            ->role(Role::Contractor)
            ->where('status', UserStatus::Active)
            ->where(Str::isUuid($key) ? 'uuid' : 'email', Str::isUuid($key) ? $key : Str::lower(trim($key)))
            ->whereHas('contractorProfile', fn ($q) => $q->where('state', $area->state))
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'owner_phone.digits' => 'Enter an 11-digit phone number, e.g. 08031234567.',
            'service_area_id.in' => 'Choose one of your area offices.',
            'form74_no.required' => 'Enter the Form 74 number.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'owner_name' => 'owner name',
            'property_address' => 'property address',
            'owner_phone' => 'phone',
            'owner_email' => 'email',
            'service_area_id' => 'area office',
            'building_type' => 'building description',
            'form74_no' => 'Form 74 number',
            'form74_last_no' => 'last Form 74 number',
        ];
    }
}
