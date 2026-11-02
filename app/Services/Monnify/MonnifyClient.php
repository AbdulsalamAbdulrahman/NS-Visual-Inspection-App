<?php

declare(strict_types=1);

namespace App\Services\Monnify;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Monnify endpoints we use. Checked against
 * developers.monnify.com (Oct 2026):
 *  - POST /api/v1/auth/login                         Basic base64(apiKey:secretKey) → accessToken, expiresIn
 *  - POST /api/v1/merchant/transactions/init-transaction  Bearer → checkoutUrl, transactionReference
 *  - GET  /api/v2/merchant/transactions/query?paymentReference=…  Bearer → paymentStatus, amountPaid
 *
 * Settlement endpoints (banks, account validate, sub-accounts, incomeSplitConfig)
 * are unconfirmed against sandbox; check them before setting MONNIFY_SPLIT_ENABLED.
 */
class MonnifyClient
{
    private const TOKEN_CACHE_KEY = 'monnify.access_token';

    public function isConfigured(): bool
    {
        return filled(config('services.monnify.api_key'))
            && filled(config('services.monnify.secret_key'))
            && filled(config('services.monnify.contract_code'));
    }

    /**
     * Start a checkout. Amount is sent in naira (Monnify's unit).
     *
     * @param  list<array{subAccountCode: string, feePercentage: int, feeBearer: bool, splitAmount: float}>  $incomeSplit
     * @return array{transactionReference: string, paymentReference: string, checkoutUrl: string, raw: array<string, mixed>}
     */
    public function initTransaction(
        int $amountKobo,
        string $paymentReference,
        string $customerName,
        string $customerEmail,
        string $description,
        string $redirectUrl,
        array $incomeSplit = [],
    ): array {
        $body = $this->authorised()->post('/api/v1/merchant/transactions/init-transaction', [
            'amount' => round($amountKobo / 100, 2),
            'customerName' => $customerName,
            'customerEmail' => $customerEmail,
            'paymentReference' => $paymentReference,
            'paymentDescription' => $description,
            'currencyCode' => 'NGN',
            'contractCode' => config('services.monnify.contract_code'),
            'redirectUrl' => $redirectUrl,
            'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER', 'USSD'],
        ] + ($incomeSplit === [] ? [] : ['incomeSplitConfig' => $incomeSplit]));

        $data = $this->responseBody($body, 'initialise the payment');

        $checkoutUrl = $data['checkoutUrl'] ?? null;
        $transactionReference = $data['transactionReference'] ?? null;

        if (! is_string($checkoutUrl) || ! is_string($transactionReference)) {
            throw new MonnifyException('Monnify did not return a checkout URL.');
        }

        return [
            'transactionReference' => $transactionReference,
            'paymentReference' => is_string($data['paymentReference'] ?? null) ? $data['paymentReference'] : $paymentReference,
            'checkoutUrl' => $checkoutUrl,
            'raw' => $data,
        ];
    }

    /**
     * Server-side status of a transaction by our reference; null if Monnify
     * doesn't know it (yet).
     *
     * @return array<string, mixed>|null
     */
    public function findByPaymentReference(string $paymentReference): ?array
    {
        $response = $this->authorised()->get('/api/v2/merchant/transactions/query', [
            'paymentReference' => $paymentReference,
        ]);

        if ($response->status() === 404) {
            return null;
        }

        return $this->responseBody($response, 'check the payment');
    }

    /**
     * Nigerian banks, cached for a day.
     *
     * @return list<array{code: string, name: string}>
     */
    public function banks(): array
    {
        /** @var list<array{code: string, name: string}> */
        return Cache::remember('monnify.banks', 86_400, function (): array {
            $response = $this->authorised()->get('/api/v1/banks');
            $json = $response->successful() ? $response->json('responseBody') : null;

            if (! is_array($json)) {
                throw new MonnifyException('Couldn\'t load the bank list.');
            }

            return collect($json)
                ->map(fn (array $bank): array => ['code' => (string) $bank['code'], 'name' => (string) $bank['name']])
                ->sortBy('name')->values()->all();
        });
    }

    /**
     * For forms: an empty list when Monnify can't be reached.
     *
     * @return list<array{code: string, name: string}>
     */
    public function banksOrNone(): array
    {
        try {
            return $this->banks();
        } catch (MonnifyException) {
            return [];
        }
    }

    /** The account holder's name, from the bank. */
    public function accountName(string $accountNumber, string $bankCode): string
    {
        $data = $this->responseBody($this->authorised()->get('/api/v1/disbursements/account/validate', [
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
        ]), 'check the account');

        return (string) ($data['accountName'] ?? throw new MonnifyException('Monnify did not return an account name.'));
    }

    /** A settlement sub-account; returns its code. */
    public function createSubAccount(string $bankCode, string $accountNumber, string $email): string
    {
        $response = $this->authorised()->post('/api/v1/sub-accounts', [[
            'currencyCode' => 'NGN',
            'bankCode' => $bankCode,
            'accountNumber' => $accountNumber,
            'email' => $email,
            'defaultSplitPercentage' => 0,
        ]]);
        $json = $response->json();
        $code = is_array($json) ? ($json['responseBody'][0]['subAccountCode'] ?? null) : null;

        if (! $response->successful() || ! is_string($code)) {
            throw new MonnifyException('Couldn\'t create the sub-account.');
        }

        return $code;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.monnify.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->connectTimeout(10);
    }

    private function authorised(): PendingRequest
    {
        return $this->http()->withToken($this->token());
    }

    /**
     * Bearer token, cached until shortly before it expires.
     */
    private function token(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if (! $this->isConfigured()) {
            throw new MonnifyException('Monnify keys are not configured.');
        }

        $response = $this->http()
            ->withBasicAuth((string) config('services.monnify.api_key'), (string) config('services.monnify.secret_key'))
            ->post('/api/v1/auth/login');

        $data = $this->responseBody($response, 'sign in to Monnify');
        $token = (string) ($data['accessToken'] ?? '');
        $ttl = max(60, (int) ($data['expiresIn'] ?? 3600) - 120);

        if ($token === '') {
            throw new MonnifyException('Monnify did not return an access token.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseBody(Response $response, string $doing): array
    {
        $json = $response->json();

        if (! $response->successful() || ! is_array($json) || ($json['requestSuccessful'] ?? false) !== true) {
            if ($response->status() === 401) {
                Cache::forget(self::TOKEN_CACHE_KEY);
            }

            $message = is_array($json) ? ($json['responseMessage'] ?? null) : null;

            throw new MonnifyException("Couldn't {$doing}".($message ? ": {$message}" : " (HTTP {$response->status()})."));
        }

        return (array) ($json['responseBody'] ?? []);
    }
}
