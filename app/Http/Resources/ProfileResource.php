<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
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
            ] : null,
            'areas' => $this->isRep() ? $this->serviceAreas->pluck('name')->all() : [],
        ];
    }
}
