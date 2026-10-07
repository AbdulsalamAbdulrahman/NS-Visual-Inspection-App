<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Accounts\DeleteAccount;
use App\Actions\Accounts\ResendLoginDetails;
use App\Actions\Accounts\SetSuspended;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;

/**
 * Row actions shared by contractors and reps (AD-06 / AM-05).
 */
class AccountController extends Controller
{
    public function resendLogin(User $user, ResendLoginDetails $resend): RedirectResponse
    {
        Gate::authorize('manage', $user);

        $resend->handle($user);

        return $this->done("Login details sent to {$user->email}");
    }

    public function sendResetLink(User $user): RedirectResponse
    {
        Gate::authorize('manage', $user);

        $status = Password::broker()->sendResetLink(['email' => $user->email]);

        return $status === Password::RESET_THROTTLED
            ? $this->done('A reset link was sent a moment ago. Try again in a minute.', 'info')
            : $this->done("Password reset link sent to {$user->email}");
    }

    public function suspend(User $user, SetSuspended $set): RedirectResponse
    {
        Gate::authorize('manage', $user);

        $set->handle($user, suspended: true);

        return $this->done("{$user->name} is suspended.", 'info');
    }

    public function reactivate(User $user, SetSuspended $set): RedirectResponse
    {
        Gate::authorize('manage', $user);

        $set->handle($user, suspended: false);

        return $this->done("{$user->name} is active again.");
    }

    public function destroy(User $user, DeleteAccount $delete): RedirectResponse
    {
        Gate::authorize('manage', $user);

        $delete->handle($user);

        return $this->done("{$user->name} was deleted. Their submitted inspections stay on record.");
    }

    private function done(string $message, string $type = 'success'): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return back();
    }
}
