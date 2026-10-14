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
 * The contractor's licence has expired: new work is blocked until they upload
 * the renewed licence (queued).
 */
class LicenceExpired extends Notification implements ShouldQueue
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
            ->subject('Your licence has expired')
            ->greeting("Hello {$notifiable->firstName()},")
            ->line("Your contractor licence on record expired on **{$date}**.")
            ->line('Until you upload the renewed licence you can’t start, pay for or resubmit inspections, and you won’t be assigned new jobs. Your reports stay available.')
            ->action('Upload the renewed licence', route('profile.show'))
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
