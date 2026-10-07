<?php

declare(strict_types=1);

namespace App\Http\Requests\Inspections;

use App\Enums\AttachmentType;
use App\Models\Inspection;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    /** Per-type limits on how many files an inspection can hold. */
    public const LIMITS = [
        'layout' => 3,
        'photo' => 12,
        'calibration' => 3,
    ];

    public function authorize(): bool
    {
        /** @var Inspection $inspection */
        $inspection = $this->route('inspection');

        return $this->user()?->can('update', $inspection) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = AttachmentType::tryFrom((string) $this->input('type'));

        // Server-side MIME sniffing (fileinfo), max 10 MB after browser compression.
        $mimes = $type === AttachmentType::Photo
            ? 'mimetypes:image/jpeg,image/png,image/webp'
            : 'mimetypes:application/pdf,image/jpeg,image/png,image/webp';

        return [
            'type' => [
                'required', Rule::enum(AttachmentType::class),
                function (string $attribute, mixed $value, Closure $fail): void {
                    /** @var Inspection $inspection */
                    $inspection = $this->route('inspection');
                    $limit = self::LIMITS[$value] ?? 0;

                    if ($inspection->attachments()->where('type', $value)->count() >= $limit) {
                        $fail("You can add up to {$limit} of these.");
                    }
                },
            ],
            'file' => ['required', 'file', 'max:10240', $mimes],
            'exif_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'exif_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'taken_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'Files can be at most 10 MB.',
            'file.mimetypes' => 'Use a PDF, JPG, PNG or WebP file.',
        ];
    }
}
