<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed when an admin creates an account or resends login details.
 */
class LoginDetails extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $temporaryPassword,
        public readonly bool $resent = false,
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
        return (new MailMessage)
            ->subject($this->resent ? 'Your new KENS login details' : 'Your KENS login details')
            ->greeting("Hello {$notifiable->firstName()},")
            ->line($this->resent
                ? 'The Kaduna Electric New Service Department has issued you new login details for the Building Electrical Inspection & Certification app.'
                : 'The Kaduna Electric New Service Department has created your account for the Building Electrical Inspection & Certification app.')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Temporary password:** `{$this->temporaryPassword}`")
            ->action('Sign in', route('login'))
            ->line('You will be asked to choose your own password when you first sign in.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
