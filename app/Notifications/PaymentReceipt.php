<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inspection;
use App\Support\DocumentUrls;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the building owner (on-demand mail route) after the inspection fee is
 * paid, and again when a contractor or NSD resends it. Queued, after commit.
 */
class PaymentReceipt extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Inspection $inspection)
    {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inspection = $this->inspection->loadMissing(['paidPayment', 'contractor']);
        $payment = $inspection->paidPayment;
        $amount = $payment ? Money::format((int) ($payment->amount_paid_kobo ?? $payment->amount_kobo)) : null;

        return (new MailMessage)
            ->subject("Payment receipt · {$inspection->ticket_no}")
            ->greeting("Hello {$inspection->owner_name},")
            ->line("The inspection fee for your building at {$inspection->property_address} has been paid.")
            ->lineIf($amount !== null, "**Amount paid:** {$amount}")
            ->line("**Ticket no.:** {$inspection->ticket_no}")
            ->lineIf(filled($payment?->transaction_reference), "**Receipt no.:** {$payment?->transaction_reference}")
            ->line("**Inspector:** {$inspection->contractor->name}")
            ->action('View receipt', DocumentUrls::forOwner('receipt', $inspection))
            ->line('The report is now with the reviewers. You will get the certificate by email once it is approved.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
