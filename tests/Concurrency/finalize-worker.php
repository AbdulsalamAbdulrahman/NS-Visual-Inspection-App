<?php

declare(strict_types=1);

/*
 * Worker for ConcurrentFinalizeTest: finalises one payment in its own PHP
 * process (own DB connection) and prints the ticket. Run with the test DB in
 * the environment, e.g. DB_DATABASE=kens_testing php finalize-worker.php 12
 */

use App\Actions\Payments\FinalizePaidInspection;
use App\Models\Payment;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$payment = Payment::query()->findOrFail((int) ($argv[1] ?? 0));

// Line the workers up so they hit the locks together.
$startAt = (float) ($argv[2] ?? 0);
while (microtime(true) < $startAt) {
    usleep(500);
}

try {
    $inspection = $app->make(FinalizePaidInspection::class)->handle($payment, [
        'transactionReference' => 'MNFY|TEST|'.$payment->payment_reference,
        'paymentReference' => $payment->payment_reference,
        'amountPaid' => $payment->amount_kobo / 100,
        'paymentStatus' => 'PAID',
        'paymentMethod' => 'CARD',
        'paidOn' => now()->toIso8601String(),
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, $e::class.': '.$e->getMessage());
    exit(1);
}

echo $inspection->ticket_no;
