<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\FirstPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * AU-03: invited users replace the emailed temporary password on first sign-in.
 */
class FirstPasswordController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->must_change_password) {
            return redirect()->route('home');
        }

        return Inertia::render('auth/FirstPassword', [
            'firstName' => $user->firstName(),
        ]);
    }

    public function update(FirstPasswordRequest $request): RedirectResponse
    {
        $request->user()->forceFill([
            'password' => $request->validated('password'),
            'must_change_password' => false,
            'status' => UserStatus::Active,
        ])->save();

        return redirect()->route('home');
    }
}
