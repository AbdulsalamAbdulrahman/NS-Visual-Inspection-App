<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CH-03 / AM-08 "Profile & password": read-only account and licence details.
 */
class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user()->loadMissing(['contractorProfile', 'serviceAreas']);

        return Inertia::render('account/Profile', [
            'profile' => ProfileResource::make($user),
        ]);
    }
}
