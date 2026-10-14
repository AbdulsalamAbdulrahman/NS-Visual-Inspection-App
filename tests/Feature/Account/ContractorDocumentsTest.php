<?php

declare(strict_types=1);

use App\Enums\InspectionStatus;
use App\Enums\NigerianState;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\LicenceExpired;
use App\Notifications\LicenceExpiringSoon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->travelTo('2026-10-14 09:00:00');
    $this->area = ServiceArea::factory()->create(['name' => 'Barnawa', 'state' => NigerianState::Kaduna]);
    $this->contractor = User::factory()->contractor()->create();
    $this->contractor->contractorProfile->update(['state' => NigerianState::Kaduna]);
});

function expireLicence(User $contractor, string $date): void
{
    $contractor->contractorProfile->update(['licence_expires_at' => $date]);
}

test('a contractor uploads their licence, passport photo, signature and expiry on Profile', function () {
    $this->actingAs($this->contractor)->post(route('profile.documents.store'), [
        'licence' => UploadedFile::fake()->create('licence.pdf', 300, 'application/pdf'),
        'passport' => UploadedFile::fake()->image('me.jpg'),
        'signature' => UploadedFile::fake()->image('sign.png'),
        'licence_expires_at' => '2027-03-12',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $profile = $this->contractor->contractorProfile->fresh();
    Storage::disk('local')->assertExists([$profile->licence_path, $profile->passport_path, $profile->signature_path]);
    expect($profile->licence_expires_at->toDateString())->toBe('2027-03-12')
        ->and($profile->documentsMissing())->toBe([]);

    $this->get(route('profile.show'))->assertInertia(fn (Assert $page) => $page
        ->where('profile.documents.status.state', 'valid')
        ->where('profile.documents.status.label', 'Licence valid until 12 Mar 2027')
        ->where('profile.documents.expiresOn', '2027-03-12'));
});

test('a drawn signature is saved, and replacing a document deletes the old file', function () {
    $png = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('apple-touch-icon.png')));

    $this->actingAs($this->contractor)->post(route('profile.documents.store'), ['signature_drawn' => $png])->assertSessionHasNoErrors();
    $first = $this->contractor->contractorProfile->fresh()->signature_path;

    $this->post(route('profile.documents.store'), ['signature' => UploadedFile::fake()->image('sign.png')])->assertSessionHasNoErrors();

    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($this->contractor->contractorProfile->fresh()->signature_path);
});

test('wrong file types and a past expiry are rejected', function () {
    $this->actingAs($this->contractor)->post(route('profile.documents.store'), [
        'licence' => UploadedFile::fake()->create('licence.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'passport' => UploadedFile::fake()->create('me.pdf', 50, 'application/pdf'),
        'licence_expires_at' => '2026-10-13',
    ])->assertSessionHasErrors(['licence', 'passport', 'licence_expires_at']);

    expect($this->contractor->contractorProfile->fresh()->licence_path)->toBeNull();
});

test('documents are visible to their contractor and admins only', function () {
    $this->actingAs($this->contractor)->post(route('profile.documents.store'), ['passport' => UploadedFile::fake()->image('me.jpg')]);

    $this->get(route('profile.documents.show', 'passport'))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.contractors.documents.show', [$this->contractor, 'passport']))->assertOk();

    $this->actingAs(User::factory()->contractor()->create())->get(route('admin.contractors.documents.show', [$this->contractor, 'passport']))->assertForbidden();
    $this->actingAs(User::factory()->rep([$this->area])->create())->get(route('admin.contractors.documents.show', [$this->contractor, 'passport']))->assertForbidden();
});

test('an expired licence blocks starting, paying and resubmitting, but not signing in', function () {
    expireLicence($this->contractor, '2026-10-13');
    $job = Inspection::factory()->assigned(User::factory()->rep([$this->area])->create(), $this->area)->forContractor($this->contractor)->create();
    $draft = Inspection::factory()->complete()->forContractor($this->contractor)->create();
    $returned = Inspection::factory()->changesRequested()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)->get(route('inspections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('licence.state', 'expired'));

    $this->post(route('inspections.start', $job))
        ->assertRedirect(route('inspections.index'))
        ->assertInertiaFlash('toast.message', 'Your licence expired on 13 Oct 2026. Upload the renewed licence and its new expiry date on Profile to continue.');
    $this->get(route('inspections.pay', $draft))->assertRedirect(route('inspections.index'));
    $this->post(route('inspections.pay.store', $draft))->assertRedirect(route('inspections.index'));
    $this->post(route('inspections.resubmit', $returned))->assertRedirect(route('inspections.index'));

    expect($job->fresh()->status)->toBe(InspectionStatus::Assigned);
});

test('a renewed licence lifts the block straight away', function () {
    expireLicence($this->contractor, '2026-10-13');
    $job = Inspection::factory()->assigned(User::factory()->rep([$this->area])->create(), $this->area)->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)->post(route('profile.documents.store'), ['licence_expires_at' => '2027-10-13'])->assertSessionHasNoErrors();
    $this->post(route('inspections.start', $job))->assertRedirect(route('inspections.edit', $job));
});

test('the licence is valid through its expiry day, and contractors with no expiry yet are not blocked', function () {
    expireLicence($this->contractor, '2026-10-14');
    expect($this->contractor->contractorProfile->fresh()->isLicenceExpired())->toBeFalse();

    $other = User::factory()->contractor()->create();
    $this->actingAs($other)->get(route('inspections.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('licence.state', 'missing')
            ->where('licence.missing', ['Licence copy', 'Licence expiry date', 'Passport photograph', 'Signature']));
});

test('reps cannot assign a contractor whose licence has expired', function () {
    expireLicence($this->contractor, '2026-10-01');
    $rep = User::factory()->rep([$this->area])->create();

    $this->actingAs($rep)->get(route('rep.jobs.create'))
        ->assertInertia(fn (Assert $page) => $page->where('contractors', []));

    $this->post(route('rep.jobs.store'), [
        'owner_name' => 'Amina Sule',
        'property_address' => '14 Ahmadu Bello Way',
        'owner_phone' => '08031234567',
        'owner_email' => 'amina@example.com',
        'service_area_id' => $this->area->id,
        'purpose' => 'residential',
        'building_type' => 'duplex',
        'form74_no' => 'F74/KD/2026/08841',
        'contractor' => $this->contractor->uuid,
    ])->assertSessionHasErrors(['contractor' => 'Choose an active contractor with a valid licence who works in Kaduna State.']);
});

test('the daily check emails a month before expiry and on expiry, once each', function () {
    expireLicence($this->contractor, '2026-11-10');
    $expired = User::factory()->contractor()->create();
    expireLicence($expired, '2026-10-10');
    $later = User::factory()->contractor()->create();
    expireLicence($later, '2027-01-01');

    $this->artisan('contractors:licence-check')->assertSuccessful();
    $this->artisan('contractors:licence-check')->assertSuccessful();

    Notification::assertSentToTimes($this->contractor, LicenceExpiringSoon::class, 1);
    Notification::assertSentToTimes($expired, LicenceExpired::class, 1);
    Notification::assertNotSentTo($later, LicenceExpiringSoon::class);

    // Renewed: a new date means new reminders later.
    $this->actingAs($this->contractor->fresh())->post(route('profile.documents.store'), ['licence_expires_at' => '2026-11-12']);
    expect($this->contractor->contractorProfile->fresh()->licence_reminded_at)->toBeNull();
});

test('starting a job copies the signature on file into the report', function () {
    $this->actingAs($this->contractor)->post(route('profile.documents.store'), ['signature' => UploadedFile::fake()->image('sign.png')]);
    $job = Inspection::factory()->assigned(User::factory()->rep([$this->area])->create(), $this->area)->forContractor($this->contractor)->create();
    $other = User::factory()->contractor()->create();
    $otherJob = Inspection::factory()->assigned(User::factory()->rep([$this->area])->create(), $this->area)->forContractor($other)->create();

    $this->post(route('inspections.start', $job))->assertRedirect();
    $this->actingAs($other)->post(route('inspections.start', $otherJob))->assertRedirect();

    $path = $job->fresh()->signature_path;
    expect($path)->toStartWith("inspections/{$job->uuid}/")
        ->and($path)->not->toBe($this->contractor->contractorProfile->fresh()->signature_path)
        ->and($otherJob->fresh()->signature_path)->toBeNull();
    Storage::disk('local')->assertExists($path);
});
