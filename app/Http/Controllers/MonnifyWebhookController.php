<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\VerifyPayment;
use App\Models\Payment;
use App\Services\Monnify\MonnifyException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /webhooks/monnify — backup to the return page. Never trusted on its
 * own: a valid notification only triggers a fresh server-side verification.
 */
class MonnifyWebhookController extends Controller
{
    public function __invoke(Request $request, VerifyPayment $verify): JsonResponse
    {
        $allowedIps = (array) config('services.monnify.webhook_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            Log::warning('Monnify webhook from unexpected IP', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Monnify signs production notifications only (not sandbox).
        if (config('services.monnify.verify_signature')) {
            $expected = hash_hmac('sha512', $request->getContent(), (string) config('services.monnify.secret_key'));

            if (! hash_equals($expected, (string) $request->header('monnify-signature'))) {
                Log::warning('Monnify webhook with a bad signature');

                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        $reference = (string) $request->input('eventData.paymentReference', '');
        $payment = $reference !== '' ? Payment::query()->where('payment_reference', $reference)->first() : null;

        if ($payment === null) {
            // Not one of ours (or not yet stored): acknowledge so Monnify stops retrying.
            return response()->json(['acknowledged' => true]);
        }

        try {
            $verify->handle($payment);
        } catch (MonnifyException $e) {
            Log::error('Monnify webhook verification failed', ['payment' => $reference, 'error' => $e->getMessage()]);

            // Let Monnify retry later.
            return response()->json(['message' => 'Verification unavailable'], 503);
        }

        return response()->json(['acknowledged' => true]);
    }
}
