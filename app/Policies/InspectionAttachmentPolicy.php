<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InspectionAttachment;
use App\Models\User;

class InspectionAttachmentPolicy
{
    public function __construct(private readonly InspectionPolicy $inspections) {}

    public function view(User $user, InspectionAttachment $attachment): bool
    {
        return $this->inspections->view($user, $attachment->inspection);
    }

    public function delete(User $user, InspectionAttachment $attachment): bool
    {
        return $this->inspections->update($user, $attachment->inspection);
    }
}
