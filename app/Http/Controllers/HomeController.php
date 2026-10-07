<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * `/` lands each role on its own home page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return $user === null
            ? redirect()->route('login')
            : redirect()->route($user->role->homeRoute());
    }
}
