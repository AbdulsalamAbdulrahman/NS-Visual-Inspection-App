<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Services\Monnify\MonnifyClient;
use App\Services\Monnify\MonnifyException;
use Illuminate\Validation\ValidationException;

class RegisterSettlementAccount
{
    public function __construct(private readonly MonnifyClient $monnify) {}

    /**
     * Confirm the account's name with the bank and create its Monnify sub-account.
     *
     * @return array{bank_code: string, bank_name: string, account_number: string, account_name: string, sub_account_code: string}
     *
     * @throws ValidationException when the account can't be confirmed
     */
    public function handle(string $bankCode, string $accountNumber, string $email): array
    {
        try {
            $bank = collect($this->monnify->banks())->firstWhere('code', $bankCode);
            throw_if($bank === null, ValidationException::withMessages(['bank_code' => 'Choose a bank from the list.']));

            $accountName = $this->monnify->accountName($accountNumber, $bankCode);
            $subAccountCode = $this->monnify->createSubAccount($bankCode, $accountNumber, $email);
        } catch (MonnifyException $e) {
            throw ValidationException::withMessages(['account_number' => 'We couldn’t confirm this account. Check the number and bank, then try again.']);
        }

        return [
            'bank_code' => $bankCode,
            'bank_name' => $bank['name'],
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'sub_account_code' => $subAccountCode,
        ];
    }
}
