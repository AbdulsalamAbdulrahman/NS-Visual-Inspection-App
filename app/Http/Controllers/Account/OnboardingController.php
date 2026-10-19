<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * First sign-in, after the password: a contractor adds their passport photo,
 * licence copy, signature and licence dates before they can take jobs
 * (EnsureAccountUsable keeps them here until then).
 */
class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user()->loadMissing('contractorProfile');

        if ($user->contractorProfile?->isOnboarded()) {
            return to_route('home');
        }

        return Inertia::render('contractor/Onboarding', [
            'firstName' => explode(' ', $user->name)[0],
            'profile' => ProfileResource::make($user),
        ]);
    }
}
