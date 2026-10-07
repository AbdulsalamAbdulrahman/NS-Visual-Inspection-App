<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class RepResource extends JsonResource
{
    use FormatsActivity;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lastActive' => $this->lastActiveLabel(),
            'areas' => $this->serviceAreas->map(fn ($area): array => [
                'id' => $area->id,
                'name' => $area->name,
            ])->values(),
        ];
    }
}
