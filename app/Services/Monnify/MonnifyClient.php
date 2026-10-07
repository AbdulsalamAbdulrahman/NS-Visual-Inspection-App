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
     * @return array{transactionReference: string, paymentReference: string, checkoutUrl: string}&array<string, mixed>
     */
    public function initTransaction(
        int $amountKobo,
        string $paymentReference,
        string $customerName,
        string $customerEmail,
        string $description,
        string $redirectUrl,
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
        ]);

        $data = $this->responseBody($body, 'initialise the payment');

        if (! isset($data['checkoutUrl'], $data['transactionReference'])) {
            throw new MonnifyException('Monnify did not return a checkout URL.');
        }

        return $data;
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
