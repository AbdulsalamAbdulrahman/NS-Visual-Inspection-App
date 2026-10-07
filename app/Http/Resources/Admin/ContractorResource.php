<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row on the admin Contractors page (AD-05 / AM-05) plus the edit form values.
 *
 * @mixin User
 */
class ContractorResource extends JsonResource
{
    use FormatsActivity;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $licence = $this->contractorProfile;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lastActive' => $this->lastActiveLabel(),
            'inspectionsCount' => (int) ($this->inspections_count ?? 0),
            'category' => $licence?->nemsa_category->value,
            'categoryLabel' => $licence?->nemsa_category->label(),
            'regNo' => $licence?->nemsa_reg_no,
            'corenNo' => $licence?->coren_no,
            'firmName' => $licence?->firm_name,
        ];
    }
}
