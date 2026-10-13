<?php

declare(strict_types=1);

use App\Enums\NigerianState;
use App\Enums\ReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\ChangesRequested;
use App\Notifications\InspectionApproved;
use App\Notifications\LoginDetails;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->barnawa = ServiceArea::factory()->create(['name' => 'Barnawa', 'state' => NigerianState::Kaduna]);
    $this->sokotoArea = ServiceArea::factory()->create(['name' => 'Sokoto North', 'state' => NigerianState::Sokoto]);
    $this->official = User::factory()->lecan(NigerianState::Kaduna)->create(['name' => 'Engr. Musa Adamu']);
});

test('a LECAN official sees the reports waiting for their state chapter', function () {
    $waiting = Inspection::factory()->submitted()->inArea($this->barnawa)->create();
    Inspection::factory()->awaitingNsd()->inArea($this->barnawa)->create();
    Inspection::factory()->submitted()->inArea($this->sokotoArea)->create();
    Inspection::factory()->complete()->inArea($this->barnawa)->create(); // draft

    $this->actingAs($this->official)->get(route('lecan.inspections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('lecan/Inspections')
            ->where('chapter', 'Kaduna')
            ->has('inspections.data', 1)
            ->where('inspections.data.0.uuid', $waiting->uuid)
            ->where('counts.all', 2)
            ->where('counts.pending_lecan', 1)
            ->where('counts.pending_nsd', 1));

    $this->get(route('lecan.inspections.index', ['review' => 'all']))
        ->assertInertia(fn (Assert $page) => $page->has('inspections.data', 2));
});

test('a LECAN official cannot open a report from another state', function () {
    $sokoto = Inspection::factory()->submitted()->inArea($this->sokotoArea)->create();

    $this->actingAs($this->official)->get(route('lecan.inspections.show', $sokoto))->assertForbidden();
    $this->post(route('lecan.inspections.approve', $sokoto))->assertForbidden();

    expect($sokoto->fresh()->review_status)->toBe(ReviewStatus::PendingLecan);
});

test('LECAN approval sends the report to NSD and records who approved it', function () {
    $report = Inspection::factory()->submitted()->inArea($this->barnawa)->create();

    $this->actingAs($this->official)->get(route('lecan.inspections.show', $report))
        ->assertInertia(fn (Assert $page) => $page->component('lecan/InspectionShow')->where('canReview', true)->missing('report.payment'));

    $this->post(route('lecan.inspections.approve', $report))->assertRedirect()->assertSessionHasNoErrors();

    $report->refresh();
    expect($report->review_status)->toBe(ReviewStatus::PendingNsd)
        ->and($report->lecan_approved_by)->toBe($this->official->id)
        ->and($report->lecan_approver_name)->toBe('Engr. Musa Adamu')
        ->and($report->approved_at)->toBeNull()
        ->and($report->reviews()->pluck('action')->all())->toBe([ReviewAction::LecanApproved]);
    Notification::assertNotSentTo($report->contractor, InspectionApproved::class);

    // Now it's NSD's turn, not LECAN's.
    $this->post(route('lecan.inspections.approve', $report))->assertForbidden();
});

test('LECAN can send a report back to the contractor with a reason', function () {
    $report = Inspection::factory()->submitted()->inArea($this->barnawa)->create();

    $this->actingAs($this->official)
        ->post(route('lecan.inspections.request-changes', $report), ['reason' => 'The earth pit photo is missing; attach it.'])
        ->assertSessionHasNoErrors();

    $report->refresh();
    expect($report->review_status)->toBe(ReviewStatus::ChangesRequested)
        ->and($report->reviewed_by)->toBe($this->official->id);
    Notification::assertSentTo($report->contractor, ChangesRequested::class, fn (ChangesRequested $n) => $n->by === 'LECAN');

    $this->actingAs($report->contractor)->get(route('inspections.show', $report))
        ->assertInertia(fn (Assert $page) => $page->where('report.review.sentBackBy', 'LECAN'));
});

test('NSD cannot act on a report while it waits for LECAN, and LECAN cannot act on NSD\'s', function () {
    $waitingLecan = Inspection::factory()->submitted()->inArea($this->barnawa)->create();
    $waitingNsd = Inspection::factory()->awaitingNsd()->inArea($this->barnawa)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.inspections.request-changes', $waitingLecan), ['reason' => 'Please re-test the earth.'])
        ->assertForbidden();

    $this->actingAs($this->official)
        ->post(route('lecan.inspections.request-changes', $waitingNsd), ['reason' => 'Please re-test the earth.'])
        ->assertForbidden();
});

test('a resubmitted report starts again with LECAN', function () {
    $returned = Inspection::factory()->changesRequested()->inArea($this->barnawa)->create([
        'lecan_approved_by' => $this->official->id,
        'lecan_approver_name' => 'Engr. Musa Adamu',
        'lecan_approved_at' => now(),
    ]);

    $this->actingAs($returned->contractor)->post(route('inspections.resubmit', $returned))->assertRedirect();

    $returned->refresh();
    expect($returned->review_status)->toBe(ReviewStatus::PendingLecan)
        ->and($returned->lecan_approved_by)->toBeNull()
        ->and($returned->lecan_approver_name)->toBeNull();
});

test('admins add LECAN officials with a state chapter and email their login details', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.lecan.store'), ['name' => 'Engr. Sani Wamakko', 'email' => 'S.Wamakko@lecan.example.com'])
        ->assertSessionHasErrors(['lecan_state' => 'Choose the state chapter they review for.']);

    $this->post(route('admin.lecan.store'), ['name' => 'Engr. Sani Wamakko', 'email' => 'S.Wamakko@lecan.example.com', 'lecan_state' => 'sokoto'])
        ->assertRedirect(route('admin.lecan.index'));

    $official = User::query()->where('email', 's.wamakko@lecan.example.com')->sole();
    expect($official->isLecan())->toBeTrue()
        ->and($official->lecan_state)->toBe(NigerianState::Sokoto)
        ->and($official->must_change_password)->toBeTrue();
    Notification::assertSentTo($official, LoginDetails::class);

    $this->get(route('admin.lecan.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/LecanOfficials')
            ->has('officials.data', 2)
            ->where('states.0.value', 'kaduna')
            ->where('states.0.staffed', true));
});

test('only admins manage LECAN officials', function () {
    $this->actingAs($this->official)->get(route('admin.lecan.index'))->assertForbidden();
    $this->actingAs(User::factory()->rep()->create())->post(route('admin.lecan.store'), [])->assertForbidden();
});
