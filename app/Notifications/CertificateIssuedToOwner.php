<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inspection;
use App\Support\DocumentUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the building owner the certificate is issued, with links to the
 * certificate and the full report (signed, no account needed). Queued.
 */
class CertificateIssuedToOwner extends Notification implements ShouldQueue
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
        $inspection = $this->inspection;

        return (new MailMessage)
            ->subject("Your building electrical certificate · {$inspection->ticket_no}")
            ->greeting("Hello {$inspection->owner_name},")
            ->line("The electrical installation of your building at {$inspection->property_address} has been approved as safe for grid connection.")
            ->line("**Certificate no.:** {$inspection->ticket_no}")
            ->action('View certificate', DocumentUrls::forOwner('certificate', $inspection))
            ->line('[View the full inspection report]('.DocumentUrls::forOwner('report', $inspection).')')
            ->line('Open either one to print it or save it as a PDF. Anyone can confirm the certificate by scanning its QR code.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
