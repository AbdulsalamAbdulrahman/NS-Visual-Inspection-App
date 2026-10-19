<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Profile → Licence & documents. Every field is optional so each tile saves
 * on its own; MIME types are sniffed server-side.
 */
class ContractorDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isContractor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'licence' => ['sometimes', 'file', 'max:5120', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp'],
            'passport' => ['sometimes', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp'],
            'signature' => ['sometimes', 'file', 'max:2048', 'mimetypes:image/jpeg,image/png,image/webp'],
            'signature_drawn' => ['sometimes', 'string', 'max:700000', 'starts_with:data:image/png;base64,'],
            'licence_issued_at' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'licence_expires_at' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * The dates save one at a time, so the expiry is checked against whichever issue date is on file.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $profile = $this->user()?->contractorProfile;
                $issued = $this->input('licence_issued_at', $profile?->licence_issued_at?->toDateString());
                $expires = $this->input('licence_expires_at', $profile?->licence_expires_at?->toDateString());

                if ($validator->errors()->isEmpty() && is_string($issued) && is_string($expires) && $expires <= $issued) {
                    $validator->errors()->add($this->has('licence_expires_at') ? 'licence_expires_at' : 'licence_issued_at', 'The licence expiry date must be after the issue date.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'licence.mimetypes' => 'Upload the licence as a PDF or a photo (JPG or PNG).',
            'passport.mimetypes' => 'Upload the passport photograph as a JPG or PNG.',
            'signature.mimetypes' => 'Upload the signature as a JPG or PNG, or draw it.',
            '*.max' => 'That file is too large (licence and photo up to 5 MB, signature up to 2 MB).',
            'licence_expires_at.after_or_equal' => 'The expiry date must be today or later. If the licence has expired, renew it first.',
            'licence_issued_at.before_or_equal' => 'The issue date can’t be in the future.',
        ];
    }
}
