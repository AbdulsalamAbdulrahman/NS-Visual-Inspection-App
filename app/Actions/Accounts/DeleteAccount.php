<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteAccount
{
    /**
     * Soft-delete: the login goes away, submitted inspections stay on record.
     * Open sessions are dropped so the user is signed out everywhere.
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->delete();

            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }
}
