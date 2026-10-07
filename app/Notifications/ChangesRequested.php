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
 * NSD sent the report back with a reason (queued).
 */
class ChangesRequested extends Notification implements ShouldQueue
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
            ->subject("Changes requested · {$inspection->ticket_no}")
            ->greeting("Hello {$notifiable->firstName()},")
            ->line("The New Service Department reviewed the report for {$inspection->owner_name} and needs some changes before issuing the certificate:")
            ->line('> '.$inspection->review_note)
            ->action('Fix and resubmit', route('inspections.edit', $inspection))
            ->line('There is nothing more to pay: the fee is already settled and the ticket number stays the same.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
