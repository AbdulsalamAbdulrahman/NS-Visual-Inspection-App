<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Services\Monnify\InvalidBankAccount;
use App\Services\Monnify\MonnifyClient;
use App\Services\Monnify\MonnifyException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

/**
 * Confirms an account's name with the bank and creates its Monnify sub-account.
 * If Monnify is unavailable the details are kept unconfirmed and
 * settlements:confirm-accounts tries again, so nobody is locked out; a payment
 * with the split on still needs the sub-account.
 */
class RegisterSettlementAccount
{
    public function __construct(private readonly MonnifyClient $monnify) {}

    /**
     * @return array{bank_code: string, bank_name: string, account_number: string, account_name: string|null, sub_account_code: string|null}
     *
     * @throws ValidationException when the bank is unknown or rejects the account
     */
    public function handle(string $bankCode, string $accountNumber, string $email): array
    {
        $bank = collect($this->monnify->banksForForm())->firstWhere('code', $bankCode);

        if ($bank === null) {
            throw ValidationException::withMessages(['bank_code' => 'Choose a bank from the list.']);
        }

        try {
            $confirmed = $this->confirm($bankCode, $accountNumber, $email);
        } catch (InvalidBankAccount) {
            throw ValidationException::withMessages(['account_number' => 'The bank didn’t recognise this account. Check the number and bank.']);
        } catch (MonnifyException|ConnectionException) {
            $confirmed = ['account_name' => null, 'sub_account_code' => null];
        }

        return ['bank_code' => $bankCode, 'bank_name' => $bank['name'], 'account_number' => $accountNumber, ...$confirmed];
    }

    /**
     * @return array{account_name: string, sub_account_code: string}
     *
     * @throws MonnifyException|ConnectionException
     */
    public function confirm(string $bankCode, string $accountNumber, string $email): array
    {
        return [
            'account_name' => $this->monnify->accountName($accountNumber, $bankCode),
            'sub_account_code' => $this->monnify->createSubAccount($bankCode, $accountNumber, $email),
        ];
    }
}
