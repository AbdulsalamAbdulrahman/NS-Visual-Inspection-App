<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Http\Resources\Concerns\FormatsActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A LECAN official in the admin list.
 *
 * @mixin User
 */
class LecanResource extends JsonResource
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
            'state' => $this->lecan_state?->value,
            'stateLabel' => $this->lecan_state?->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'lastActive' => $this->lastActiveLabel(),
        ];
    }
}
