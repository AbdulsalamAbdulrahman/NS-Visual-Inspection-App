<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Money;
use App\Support\PublicFees;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * `/` is the public home page for visitors and lands each signed-in role
     * on its own home page.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null) {
            return redirect()->route($user->role->homeRoute());
        }

        $percent = (float) config('services.monnify.charge_percent');
        $cap = (int) config('services.monnify.charge_cap_kobo');

        return Inertia::render('public/Home', [
            'fees' => PublicFees::groups(),
            'charge' => $percent > 0
                ? rtrim(rtrim(number_format($percent, 2), '0'), '.').'%'.($cap > 0 ? ', capped at '.Money::format($cap) : '')
                : null,
        ]);
    }
}
