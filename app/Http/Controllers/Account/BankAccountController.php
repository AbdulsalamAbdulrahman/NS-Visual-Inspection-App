<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Payments\RegisterSettlementAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\BankAccountRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * The account an inspector's share of each fee settles to.
 */
class BankAccountController extends Controller
{
    public function store(BankAccountRequest $request, RegisterSettlementAccount $register): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->contractorProfile;
        abort_if($profile === null, 404);

        $profile->update($register->handle($request->string('bank_code')->toString(), $request->string('account_number')->toString(), $user->email));

        Inertia::flash('toast', ['type' => 'success', 'message' => $profile->account_name ? "Saved: {$profile->account_name}." : 'Saved. We’ll confirm it with your bank shortly.']);

        return back();
    }
}
