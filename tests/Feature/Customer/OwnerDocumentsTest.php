<?php

declare(strict_types=1);

use App\Actions\Payments\FinalizePaidInspection;
use App\Enums\PaymentStatus;
use App\Models\Inspection;
use App\Models\InspectionAttachment;
use App\Models\Payment;
use App\Models\ServiceArea;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CertificateIssuedToOwner;
use App\Notifications\PaymentReceipt;
use App\Support\DocumentUrls;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Payments\MonnifyFake;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->area = ServiceArea::factory()->create(['name' => 'Barnawa']);
    $this->contractor = User::factory()->contractor()->create();
});

function paidInspection(array $attributes = [], string $state = 'submitted'): Inspection
{
    $inspection = Inspection::factory()->{$state}()->forContractor(test()->contractor)->inArea(test()->area)
        ->create(['owner_email' => 'amina@example.com', ...$attributes]);

    Payment::factory()->forInspection($inspection)->create([
        'status' => PaymentStatus::Paid,
        'transaction_reference' => 'MNFY|20261014|000042',
        'amount_kobo' => 6_000_000,
        'amount_paid_kobo' => 6_000_000,
        'paid_at' => now(),
    ]);

    return $inspection;
}

/** On-demand mail to this address. */
function toOwner(string $email = 'amina@example.com'): Closure
{
    return fn ($notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === $email;
}

test('paying the fee emails the owner a receipt, and skips owners without an email', function () {
    $finalize = app(FinalizePaidInspection::class);

    $withEmail = Payment::factory()->create();
    $withEmail->inspection->update(['owner_email' => 'amina@example.com']);
    $finalize->handle($withEmail, MonnifyFake::transaction($withEmail->payment_reference));

    $withoutEmail = Payment::factory()->create();
    $finalize->handle($withoutEmail, MonnifyFake::transaction($withoutEmail->payment_reference));

    Notification::assertSentOnDemand(PaymentReceipt::class, toOwner());
    Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);
});

test('the receipt prints for the contractor, admin and the area rep, not other contractors', function () {
    $inspection = paidInspection(['structure_count' => 20]);

    foreach ([$this->contractor, User::factory()->admin()->create(), User::factory()->rep([$this->area])->create()] as $user) {
        $this->actingAs($user)->get(route('inspections.receipt', $inspection))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('print/Receipt')
                ->where('receipt.receiptNo', 'MNFY|20261014|000042')
                ->where('receipt.structures', 20)
                ->where('receipt.unit', '₦3,000.00')
                ->where('receipt.total', '₦60,000.00'));
    }

    $this->actingAs(User::factory()->contractor()->create())->get(route('inspections.receipt', $inspection))->assertForbidden();
});

test('the receipt can be resent to the owner, and not when there is no email', function () {
    $inspection = paidInspection();

    $this->actingAs($this->contractor)->post(route('inspections.receipt.send', $inspection))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Receipt sent to amina@example.com.');
    Notification::assertSentOnDemand(PaymentReceipt::class, toOwner());

    $inspection->update(['owner_email' => null]);
    $this->post(route('inspections.receipt.send', $inspection))->assertRedirect();
    Notification::assertSentOnDemandTimes(PaymentReceipt::class, 1);

    $this->actingAs(User::factory()->contractor()->create())->post(route('inspections.receipt.send', $inspection))->assertForbidden();
});

test('approving the report emails the owner signed links to the certificate and report', function () {
    Storage::disk('local')->put('settings/nsd-signature.png', 'png-bytes');
    Setting::put(Setting::SIGNATORY_NAME, 'Engr. Hauwa Abdullahi');
    Setting::put(Setting::SIGNATORY_TITLE, 'Head, New Service Department');
    Setting::put(Setting::SIGNATORY_SIGNATURE, 'settings/nsd-signature.png');
    $inspection = paidInspection([], 'awaitingNsd');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.inspections.approve', $inspection))
        ->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(CertificateIssuedToOwner::class, function (CertificateIssuedToOwner $notification, array $channels, AnonymousNotifiable $notifiable) {
        $mail = $notification->toMail($notifiable);

        return $notifiable->routes['mail'] === 'amina@example.com'
            && str_contains((string) $mail->actionUrl, '/customer/inspections/'.$notification->inspection->uuid.'/certificate?signature=');
    });
});

test('the owner opens signed links without signing in; unsigned or tampered links are refused', function () {
    $inspection = paidInspection([], 'approved');

    $this->get(DocumentUrls::forOwner('certificate', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('print/Certificate')->where('backUrl', null));
    $this->get(DocumentUrls::forOwner('report', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('print/Report')
            ->where('certificateUrl', fn (string $url) => str_contains($url, 'signature='))
            ->where('report.d.signatureUrl', fn (?string $url) => $url === null || str_contains($url, '/customer/inspections/')));
    $this->get(DocumentUrls::forOwner('receipt', $inspection))->assertOk();

    $this->get(route('customer.certificate', $inspection))->assertForbidden();
    $this->get(DocumentUrls::forOwner('certificate', $inspection).'x')->assertForbidden();
});

test('certificate and report links only work once the report is approved', function () {
    $inspection = paidInspection();

    $this->get(DocumentUrls::forOwner('certificate', $inspection))->assertNotFound();
    $this->get(DocumentUrls::forOwner('report', $inspection))->assertNotFound();
    $this->get(DocumentUrls::forOwner('receipt', $inspection))->assertOk();
});

test('images on the owner\'s report are served through signed links', function () {
    $inspection = paidInspection(['signature_path' => 'inspections/sig.png'], 'approved');
    Storage::disk('local')->put('inspections/sig.png', "\x89PNG");
    $photo = InspectionAttachment::query()->where('inspection_id', $inspection->id)->firstOrFail();
    Storage::disk('local')->put($photo->path, 'jpeg-bytes');

    $page = $this->get(DocumentUrls::forOwner('report', $inspection))->viewData('page');
    $attachmentUrl = collect($page['props']['report']['attachments'])->firstWhere('uuid', $photo->uuid)['url'];

    $this->get($page['props']['report']['d']['signatureUrl'])->assertOk();
    $this->get($attachmentUrl)->assertOk();
    $this->get(route('customer.files.attachment', $photo))->assertForbidden();
});
