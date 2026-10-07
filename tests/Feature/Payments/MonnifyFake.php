<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use Illuminate\Support\Facades\Http;

/**
 * Http::fake responses shaped like Monnify's (requestSuccessful/responseBody).
 */
final class MonnifyFake
{
    public static function configure(): void
    {
        config([
            'services.monnify.base_url' => 'https://sandbox.monnify.com',
            'services.monnify.api_key' => 'MK_TEST_KEY',
            'services.monnify.secret_key' => 'TEST_SECRET',
            'services.monnify.contract_code' => '1234567890',
            'services.monnify.verify_signature' => false,
            'services.monnify.webhook_ips' => [],
        ]);
    }

    /**
     * @param  array<string, mixed>|(\Closure(string): array<string, mixed>)|null  $transaction
     *                                                                                           what the query endpoint returns (null = 404); a closure receives the paymentReference
     */
    public static function fake(array|\Closure|null $transaction = null): void
    {
        self::configure();

        Http::fake([
            'sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseMessage' => 'success',
                'responseBody' => ['accessToken' => 'test-token', 'expiresIn' => 3600],
            ]),
            'sandbox.monnify.com/api/v1/merchant/transactions/init-transaction' => fn ($request) => Http::response([
                'requestSuccessful' => true,
                'responseMessage' => 'success',
                'responseBody' => [
                    'transactionReference' => 'MNFY|20261007|'.substr(md5($request['paymentReference']), 0, 7),
                    'paymentReference' => $request['paymentReference'],
                    'checkoutUrl' => 'https://sandbox.sdk.monnify.com/checkout/'.urlencode($request['paymentReference']),
                ],
            ]),
            'sandbox.monnify.com/api/v2/merchant/transactions/query*' => function ($request) use ($transaction) {
                if ($transaction === null) {
                    return Http::response(['requestSuccessful' => false, 'responseMessage' => 'Not found'], 404);
                }

                $body = $transaction instanceof \Closure ? $transaction((string) $request['paymentReference']) : $transaction;

                return Http::response(['requestSuccessful' => true, 'responseMessage' => 'success', 'responseBody' => $body]);
            },
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function transaction(string $paymentReference, string $status = 'PAID', float $amountPaid = 15000): array
    {
        return [
            'transactionReference' => 'MNFY|20261007|'.substr(md5($paymentReference), 0, 7),
            'paymentReference' => $paymentReference,
            'amountPaid' => $amountPaid,
            'totalPayable' => 15000,
            'paidOn' => '2026-10-07T10:34:00.000Z',
            'paymentStatus' => $status,
            'paymentMethod' => 'ACCOUNT_TRANSFER',
            'currency' => 'NGN',
        ];
    }
}
