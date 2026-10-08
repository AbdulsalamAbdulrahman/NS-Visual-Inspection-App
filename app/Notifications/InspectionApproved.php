<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inspection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * NSD approved the report: the certificate is ready (queued).
 */
class InspectionApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Inspection $inspection)
    {
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
            ->subject("Certificate issued · {$inspection->ticket_no}")
            ->greeting("Hello {$notifiable->firstName()},")
            ->line('The New Service Department has reviewed and approved your inspection report. The certificate is ready to print.')
            ->line("**Certificate no.:** {$inspection->ticket_no}")
            ->line("**Owner:** {$inspection->owner_name}")
            ->line("**Address:** {$inspection->property_address}")
            ->action('Print certificate', route('inspections.certificate', $inspection))
            ->line('Anyone can confirm the certificate by scanning its QR code.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
