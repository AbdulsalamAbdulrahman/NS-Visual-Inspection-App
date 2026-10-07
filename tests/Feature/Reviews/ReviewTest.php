<?php

declare(strict_types=1);

use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\ChangesRequested;
use App\Notifications\InspectionApproved;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
    $this->contractor = User::factory()->contractor()->create();
    $this->inspection = Inspection::factory()->submitted()->forContractor($this->contractor)->create();
});

function setSignatory(string $name = 'Engr. Hauwa Abdullahi'): void
{
    Storage::disk('local')->put('settings/nsd-signature.png', 'png-bytes');
    Setting::put(Setting::SIGNATORY_NAME, $name);
    Setting::put(Setting::SIGNATORY_TITLE, 'Head, New Service Department');
    Setting::put(Setting::SIGNATORY_SIGNATURE, 'settings/nsd-signature.png');
}

test('a paid inspection enters the review queue', function () {
    expect($this->inspection->review_status)->toBe(ReviewStatus::Pending);

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.show', $this->inspection))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canReview', true)
            ->where('certificateUrl', null)
            ->where('report.review.status', 'pending'));
});

test('approving snapshots the signatory, logs history and emails the contractor', function () {
    setSignatory();

    $this->actingAs($this->admin)
        ->post(route('admin.inspections.approve', $this->inspection))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $inspection = $this->inspection->fresh();

    expect($inspection->review_status)->toBe(ReviewStatus::Approved)
        ->and($inspection->reviewed_by)->toBe($this->admin->id)
        ->and($inspection->approved_at)->not->toBeNull()
        ->and($inspection->signatory_name)->toBe('Engr. Hauwa Abdullahi')
        ->and($inspection->signatory_signature_path)->toBe("inspections/{$inspection->uuid}/nsd-signature.png")
        ->and($inspection->reviews()->pluck('action')->all())->toBe([ReviewAction::Approved]);

    Storage::disk('local')->assertExists($inspection->signatory_signature_path);
    Notification::assertSentTo($this->contractor, InspectionApproved::class);

    // Changing the signatory later doesn't alter the issued certificate.
    setSignatory('Someone Else');
    expect($inspection->fresh()->signatory_name)->toBe('Engr. Hauwa Abdullahi');
});

test('approval needs a signatory first', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.inspections.approve', $this->inspection))
        ->assertSessionHasErrors('review');

    expect($this->inspection->fresh()->review_status)->toBe(ReviewStatus::Pending);
});

test('only reports awaiting review can be reviewed', function () {
    setSignatory();
    $approved = Inspection::factory()->approved()->create();
    $draft = Inspection::factory()->complete()->create();

    $this->actingAs($this->admin);
    $this->post(route('admin.inspections.approve', $approved))->assertForbidden();
    $this->post(route('admin.inspections.approve', $draft))->assertForbidden();
    $this->post(route('admin.inspections.request-changes', $approved), ['reason' => 'Please change this thing.'])->assertForbidden();
});

test('reps and contractors cannot review', function () {
    setSignatory();
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)->post(route('admin.inspections.approve', $this->inspection))->assertForbidden();
    $this->actingAs($this->contractor)->post(route('admin.inspections.approve', $this->inspection))->assertForbidden();

    expect($this->inspection->fresh()->review_status)->toBe(ReviewStatus::Pending);
});

test('requesting changes needs a reason, reopens the report and emails it', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.inspections.request-changes', $this->inspection), ['reason' => 'short'])
        ->assertSessionHasErrors('reason');

    $this->post(route('admin.inspections.request-changes', $this->inspection), [
        'reason' => 'Earth resistance is 2.6 Ω; improve the electrode and re-test.',
    ])->assertSessionHasNoErrors();

    $inspection = $this->inspection->fresh();
    expect($inspection->review_status)->toBe(ReviewStatus::ChangesRequested)
        ->and($inspection->review_note)->toContain('re-test')
        ->and($inspection->isEditable())->toBeTrue();

    Notification::assertSentTo($this->contractor, ChangesRequested::class);
});

test('the contractor can edit a returned report and resubmit without paying again', function () {
    $returned = Inspection::factory()->changesRequested()->forContractor($this->contractor)->create();
    $ticket = $returned->ticket_no;

    $this->actingAs($this->contractor)
        ->get(route('inspections.edit', $returned))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/InspectionForm')
            ->where('changesRequested.ticketNo', $ticket));

    $this->putJson(route('inspections.draft', $returned->uuid), ['owner_name' => 'Corrected Owner'])->assertOk();

    // Paying is only for drafts.
    $this->get(route('inspections.pay', $returned))->assertForbidden();
    $this->post(route('inspections.pay.store', $returned))->assertForbidden();

    $this->post(route('inspections.resubmit', $returned))->assertRedirect(route('inspections.show', $returned));

    $fresh = $returned->fresh();
    expect($fresh->review_status)->toBe(ReviewStatus::Pending)
        ->and($fresh->ticket_no)->toBe($ticket)
        ->and($fresh->owner_name)->toBe('Corrected Owner')
        ->and($fresh->isEditable())->toBeFalse()
        ->and(Payment::query()->where('inspection_id', $fresh->id)->count())->toBe(0)
        ->and($fresh->reviews()->pluck('action')->all())->toBe([ReviewAction::Resubmitted]);
});

test('an incomplete returned report cannot be resubmitted', function () {
    $returned = Inspection::factory()->changesRequested()->forContractor($this->contractor)->create();
    $returned->update(['owner_name' => null]);

    $this->actingAs($this->contractor)
        ->post(route('inspections.resubmit', $returned))
        ->assertSessionHasErrors('inspection');

    expect($returned->fresh()->review_status)->toBe(ReviewStatus::ChangesRequested);
});

test('reports under review or approved stay locked for the contractor', function () {
    $approved = Inspection::factory()->approved()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor);

    foreach ([$this->inspection, $approved] as $inspection) {
        $this->putJson(route('inspections.draft', $inspection->uuid), ['owner_name' => 'Hijack'])->assertForbidden();
        $this->post(route('inspections.resubmit', $inspection))->assertForbidden();
        $this->get(route('inspections.edit', $inspection))->assertRedirect(route('inspections.show', $inspection));
    }
});

test('another contractor cannot resubmit someone else\'s report', function () {
    $returned = Inspection::factory()->changesRequested()->forContractor($this->contractor)->create();

    $this->actingAs(User::factory()->contractor()->create())
        ->post(route('inspections.resubmit', $returned))
        ->assertForbidden();
});

test('home lists returned reports first and the admin list filters by review status', function () {
    Inspection::factory()->changesRequested()->forContractor($this->contractor)->create();
    Inspection::factory()->approved()->create();

    $this->actingAs($this->contractor)
        ->get(route('inspections.index'))
        ->assertInertia(fn (Assert $page) => $page->has('returned', 1)->where('returned.0.review', 'changes_requested'));

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.index', ['review' => 'approved']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inspections.data', 1)
            ->where('inspections.data.0.review', 'approved')
            ->where('reviewCounts', ['pending' => 1, 'changes_requested' => 1, 'approved' => 1])
            ->where('adminNav.pendingReview', 1));
});
