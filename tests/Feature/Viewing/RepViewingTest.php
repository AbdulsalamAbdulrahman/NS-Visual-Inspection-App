<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->barnawa = ServiceArea::factory()->create(['name' => 'Barnawa']);
    $this->kawo = ServiceArea::factory()->create(['name' => 'Kawo']);
    $this->zaria = ServiceArea::factory()->create(['name' => 'Zaria']);
    $this->rep = User::factory()->rep([$this->barnawa, $this->kawo])->create();
});

test('reps only see submitted inspections in their areas', function () {
    $mine = Inspection::factory()->submitted()->inArea($this->barnawa)->create(['owner_name' => 'Musa Bello']);
    Inspection::factory()->submitted()->inArea($this->kawo)->create();
    Inspection::factory()->submitted()->inArea($this->zaria)->create(['owner_name' => 'Elsewhere']);
    Inspection::factory()->complete()->inArea($this->barnawa)->create(['owner_name' => 'Still a draft']);

    $this->actingAs($this->rep)
        ->get(route('rep.inspections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rep/Inspections')
            ->where('total', 2)
            ->has('inspections.data', 2)
            ->where('areas', [
                ['id' => $this->barnawa->id, 'name' => 'Barnawa', 'count' => 1],
                ['id' => $this->kawo->id, 'name' => 'Kawo', 'count' => 1],
            ])
            ->missing('inspections.data.0.amount')
            ->has('filterOptions.areas', 2));

    $this->get(route('rep.inspections.index', ['areas' => $this->barnawa->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inspections.data', 1)
            ->where('inspections.data.0.uuid', $mine->uuid));
});

test('an area filter outside the rep\'s areas returns nothing', function () {
    Inspection::factory()->submitted()->inArea($this->zaria)->create();

    $this->actingAs($this->rep)
        ->get(route('rep.inspections.index', ['areas' => $this->zaria->id]))
        ->assertInertia(fn (Assert $page) => $page->has('inspections.data', 0));
});

test('reps can open reports in their areas but not others', function () {
    $mine = Inspection::factory()->submitted()->inArea($this->kawo)->create();
    $other = Inspection::factory()->submitted()->inArea($this->zaria)->create();

    $this->actingAs($this->rep)
        ->get(route('rep.inspections.show', $mine))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rep/InspectionShow')
            ->where('report.ticketNo', $mine->ticket_no)
            ->missing('report.payment'));

    $this->get(route('rep.inspections.show', $other))->assertForbidden();
});

test('reps cannot open drafts even in their own areas', function () {
    $draft = Inspection::factory()->complete()->inArea($this->barnawa)->create();

    $this->actingAs($this->rep)->get(route('rep.inspections.show', $draft))->assertForbidden();
});

test('reps cannot download attachments or signatures from other areas', function () {
    $mine = Inspection::factory()->submitted()->inArea($this->barnawa)->create();
    $other = Inspection::factory()->submitted()->inArea($this->zaria)->create();

    $ok = $mine->attachments()->firstOrFail();
    Storage::disk('local')->put($ok->path, 'jpeg-bytes');

    $this->actingAs($this->rep)->get(route('attachments.show', $ok))->assertOk();
    $this->get(route('attachments.show', $other->attachments()->firstOrFail()))->assertForbidden();
    $this->get(route('inspections.signature', $other))->assertForbidden();
});

test('reps with no submissions get the empty state data', function () {
    $this->actingAs($this->rep)
        ->get(route('rep.inspections.index'))
        ->assertInertia(fn (Assert $page) => $page->where('total', 0)->has('inspections.data', 0));
});

test('reps cannot reach admin lists or exports', function () {
    $this->actingAs($this->rep);

    $this->get(route('admin.inspections.index'))->assertForbidden();
    $this->get(route('admin.payments.index'))->assertForbidden();
    $this->get(route('admin.inspections.export'))->assertForbidden();
});
