<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Admins manage contractor and rep accounts (not other admins, not themselves).
     */
    public function manage(User $actor, User $target): bool
    {
        return $actor->isAdmin() && ! $target->isAdmin() && ! $actor->is($target);
    }
}
