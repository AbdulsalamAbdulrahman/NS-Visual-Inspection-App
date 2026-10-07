<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inspection;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirmation to the contractor once payment is confirmed (queued).
 */
class InspectionSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Inspection $inspection,
        public readonly Payment $payment,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $inspection = $this->inspection;

        return (new MailMessage)
            ->subject("Inspection submitted · {$inspection->ticket_no}")
            ->greeting("Hello {$notifiable->firstName()},")
            ->line('Your payment was confirmed and the inspection report has been submitted to the New Service Department for review.')
            ->line("**Ticket:** {$inspection->ticket_no}")
            ->line("**Owner:** {$inspection->owner_name}")
            ->line("**Address:** {$inspection->property_address}")
            ->line('**Amount paid:** '.Money::format($this->payment->amount_paid_kobo ?? $this->payment->amount_kobo))
            ->line("**Payment reference:** {$this->payment->transaction_reference}")
            ->action('View report', route('inspections.ticket', $inspection))
            ->line('We\'ll email you when the certificate is issued, or if NSD needs you to change anything.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
