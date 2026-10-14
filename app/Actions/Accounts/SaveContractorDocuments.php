<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\ContractorProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * A contractor's own documents (Profile → Licence & documents): any of the
 * licence copy, passport photograph, signature (uploaded image or drawn PNG)
 * and the licence expiry. Files are written before the database save and the
 * replaced ones deleted after it, so a failed save never loses a document.
 */
class SaveContractorDocuments
{
    /**
     * @param  array<string, mixed>  $data  validated ContractorDocumentsRequest data
     */
    public function handle(ContractorProfile $profile, array $data): ContractorProfile
    {
        $disk = Storage::disk('local');
        $directory = 'contractors/'.$profile->user->uuid;
        $written = [];
        $replaced = [];
        $fields = [];

        foreach (['licence', 'passport', 'signature'] as $document) {
            $file = $data[$document] ?? null;
            $path = null;

            if ($file instanceof UploadedFile) {
                $path = $file->storeAs($directory, $document.'-'.Str::random(8).'.'.($file->extension() ?: 'bin'), 'local') ?: null;
            } elseif ($document === 'signature' && filled($data['signature_drawn'] ?? null)) {
                $png = base64_decode(Str::after((string) $data['signature_drawn'], 'base64,'), true);

                if ($png !== false && str_starts_with($png, "\x89PNG")) {
                    $path = $directory.'/signature-'.Str::random(8).'.png';
                    $disk->put($path, $png);
                }
            }

            if ($path !== null) {
                $written[] = $path;
                $replaced[] = $profile->documentPath($document);
                $fields[$document.'_path'] = $path;
            }
        }

        if (array_key_exists('licence_expires_at', $data)) {
            $fields['licence_expires_at'] = $data['licence_expires_at'];

            // A new expiry date means new reminder emails.
            if ($profile->licence_expires_at?->toDateString() !== $data['licence_expires_at']) {
                $fields['licence_reminded_at'] = null;
                $fields['licence_expired_notified_at'] = null;
            }
        }

        try {
            DB::transaction(fn () => $profile->fill($fields)->save());
        } catch (Throwable $e) {
            $disk->delete($written);

            throw $e;
        }

        $disk->delete(array_values(array_filter($replaced)));

        return $profile;
    }
}
