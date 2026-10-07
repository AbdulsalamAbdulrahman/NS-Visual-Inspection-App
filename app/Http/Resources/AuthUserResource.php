<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The signed-in user as shared on every Inertia response (types/auth.ts).
 *
 * @mixin User
 */
class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $category = $this->isContractor() ? $this->contractorProfile?->nemsa_category : null;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'initials' => $this->initials(),
            'subtitle' => $category ? "{$this->role->label()} · {$category->label()}" : $this->role->label(),
            'badge' => $category?->label(),
            'areas' => $this->isRep() ? $this->serviceAreas->pluck('name')->values()->all() : [],
        ];
    }
}
