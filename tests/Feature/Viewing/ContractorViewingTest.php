<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->contractor = User::factory()->contractor()->create();
});

test('contractors can view their own submitted report without payment data', function () {
    $inspection = Inspection::factory()->submitted()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)
        ->get(route('inspections.show', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/InspectionShow')
            ->where('report.ticketNo', $inspection->ticket_no)
            ->missing('report.payment'));
});

test('contractors cannot view another contractor\'s report', function () {
    $theirs = Inspection::factory()->submitted()->create();

    $this->actingAs($this->contractor)->get(route('inspections.show', $theirs))->assertForbidden();
    $this->get(route('attachments.show', $theirs->attachments()->firstOrFail()))->assertForbidden();
    $this->get(route('inspections.signature', $theirs))->assertForbidden();
});

test('contractors cannot reach the rep or admin lists', function () {
    $this->actingAs($this->contractor);

    $this->get(route('rep.inspections.index'))->assertForbidden();
    $this->get(route('admin.inspections.index'))->assertForbidden();
    $this->get(route('admin.overview'))->assertForbidden();
});
