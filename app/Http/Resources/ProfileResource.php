<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Support\LicenceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $licence = $this->isContractor() ? $this->contractorProfile : null;

        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'initials' => $this->initials(),
            'role' => $this->role->value,
            'roleLabel' => $this->role->label(),
            'licence' => $licence ? [
                'category' => $licence->nemsa_category->label(),
                'regNo' => $licence->nemsa_reg_no,
                'corenNo' => $licence->coren_no,
                'firmName' => $licence->firm_name,
                'engineerName' => $licence->engineer_name,
                'engineerRegNo' => $licence->engineer_reg_no,
            ] : null,
            'areas' => $this->isRep() ? $this->serviceAreas->pluck('name')->all() : [],
            // Profile → Licence & documents (contractors upload their own).
            'documents' => $licence ? [
                'status' => LicenceStatus::for($licence),
                'expiresOn' => $licence->licence_expires_at?->toDateString(),
                'licenceUrl' => $licence->licence_path ? route('profile.documents.show', 'licence').'?v='.md5($licence->licence_path) : null,
                'licenceIsPdf' => $licence->licence_path !== null && str_ends_with(strtolower($licence->licence_path), '.pdf'),
                'passportUrl' => $licence->passport_path ? route('profile.documents.show', 'passport').'?v='.md5($licence->passport_path) : null,
                'signatureUrl' => $licence->signature_path ? route('profile.documents.show', 'signature').'?v='.md5($licence->signature_path) : null,
                'storeUrl' => route('profile.documents.store'),
            ] : null,
        ];
    }
}
