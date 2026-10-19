<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContractorProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * About a month before the contractor's licence expires (queued).
 */
class LicenceExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContractorProfile $profile) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $date = $this->profile->licence_expires_at?->format('d M Y');

        return (new MailMessage)
            ->subject("Your licence expires on {$date}")
            ->greeting("Hello {$notifiable->firstName()},")
            ->line("Your contractor licence on record expires on **{$date}**.")
            ->line('After that date you can still sign in, but you can’t start, pay for or resubmit inspections, and you won’t be assigned new jobs.')
            ->action('Upload the renewed licence', route('profile.show'))
            ->line('Upload the renewed licence and its new expiry date on your Profile before then to keep working.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
