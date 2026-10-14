<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

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
            'licence_expires_at' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today'],
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
        ];
    }
}
