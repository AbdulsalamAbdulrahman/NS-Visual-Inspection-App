<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\LoginDetails;
use Symfony\Component\Mime\Email;

test('emails carry the Kaduna Electric logo inline and the KENS theme', function () {
    $user = User::factory()->create(['name' => 'Amina Bello']);

    $user->notify(new LoginDetails('Kd7-pQ2x-91'));

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);

    /** @var Email $email */
    $email = $messages->first()->getOriginalMessage();
    $inline = collect($email->getAttachments())->filter(fn ($part) => $part->getDisposition() === 'inline');

    expect($inline)->toHaveCount(1)
        ->and($email->getHtmlBody())
        ->toContain('src="cid:'.$inline->first()->getContentId().'"')
        ->toContain('New Service Department')
        ->toContain('#09502e')
        ->and($email->getTextBody())
        ->toContain('Kd7-pQ2x-91')
        ->not->toContain('cid:');
});
