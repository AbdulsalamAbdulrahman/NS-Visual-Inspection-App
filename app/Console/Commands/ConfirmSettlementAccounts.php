<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payments\RegisterSettlementAccount;
use App\Models\ContractorProfile;
use App\Models\SettlementParty;
use App\Services\Monnify\InvalidBankAccount;
use App\Services\Monnify\MonnifyException;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

/**
 * Retries accounts saved while Monnify was unavailable. One the bank rejects
 * is cleared, which sends an inspector back to onboarding to enter it again.
 */
class ConfirmSettlementAccounts extends Command
{
    protected $signature = 'settlements:confirm-accounts';

    protected $description = 'Confirm bank accounts saved while Monnify was unavailable';

    public function handle(RegisterSettlementAccount $register): int
    {
        $profiles = ContractorProfile::query()->with('user')->whereNotNull('account_number')->whereNull('sub_account_code')->get();
        $parties = SettlementParty::query()->whereNotNull('account_number')->whereNull('sub_account_code')->get();

        foreach ($profiles as $profile) {
            if (! $this->retry($register, $profile, $profile->user->email)) {
                return self::SUCCESS;
            }
        }

        foreach ($parties as $party) {
            if (! $this->retry($register, $party, (string) config('kens.nsd.email'))) {
                return self::SUCCESS;
            }
        }

        return self::SUCCESS;
    }

    /** False when Monnify is still unavailable: stop until the next run. */
    private function retry(RegisterSettlementAccount $register, ContractorProfile|SettlementParty $account, string $email): bool
    {
        try {
            $account->update($register->confirm((string) $account->bank_code, (string) $account->account_number, $email));
        } catch (InvalidBankAccount) {
            $account->update(['bank_code' => null, 'bank_name' => null, 'account_number' => null]);
        } catch (MonnifyException|ConnectionException) {
            return false;
        }

        return true;
    }
}
