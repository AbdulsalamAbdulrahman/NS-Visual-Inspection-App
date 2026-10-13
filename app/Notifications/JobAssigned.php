<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Inspection;
use App\Models\User;
use App\Support\Form74Range;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A rep assigned a job (queued). The contractor gets the job details; the
 * building owner, when they gave an email, learns who will inspect.
 */
class JobAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Inspection $job)
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
        $job = $this->job->loadMissing(['contractor.contractorProfile', 'serviceArea']);
        $form74 = Form74Range::label($job->form74_no, $job->form74_last_no);
        $structures = $job->structure_count > 1 ? " ({$job->structure_count} structures)" : '';

        if ($notifiable instanceof User) {
            return (new MailMessage)
                ->subject("New inspection job · {$job->owner_name}")
                ->greeting("Hello {$notifiable->firstName()},")
                ->line('The New Service Department has assigned you a building electrical inspection.')
                ->line("**Owner:** {$job->owner_name} · {$job->owner_phone}")
                ->line("**Address:** {$job->property_address}")
                ->line("**Area office:** {$job->serviceArea?->name}")
                ->line("**Form 74:** {$form74}{$structures}")
                ->action('Open my jobs', route('inspections.index'))
                ->line('Tap **Start inspection** on the job when you are at the property. The customer details are filled in for you.')
                ->salutation('— New Service Department, Kaduna Electric');
        }

        $contractor = $job->contractor;
        $firm = $contractor->contractorProfile?->firm_name;

        return (new MailMessage)
            ->subject('Your building electrical inspection')
            ->greeting("Hello {$job->owner_name},")
            ->line("Kaduna Electric has scheduled the electrical inspection of your building at {$job->property_address}.")
            ->line('**Inspector:** '.$contractor->name.($firm ? " ({$firm})" : '').', a NEMSA-licensed electrical contractor.')
            ->lineIf(filled($contractor->phone), "**Inspector's phone:** {$contractor->phone}")
            ->line("**Form 74:** {$form74}{$structures}")
            ->line('The contractor will contact you to arrange a visit. After the inspection the report is reviewed before a certificate is issued.')
            ->salutation('— New Service Department, Kaduna Electric');
    }
}
