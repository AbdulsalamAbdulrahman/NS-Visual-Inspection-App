<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('area editing is off by default: the list is read-only', function () {
    $area = ServiceArea::factory()->create(['name' => 'Kawo']);

    $this->actingAs($this->admin)
        ->get(route('admin.areas.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('editable', false)->has('areas', 1));

    $this->post(route('admin.areas.store'), ['name' => 'Somewhere'])->assertForbidden();
    $this->put(route('admin.areas.update', $area), ['name' => 'Renamed'])->assertForbidden();
    $this->post(route('admin.areas.toggle', $area))->assertForbidden();

    expect(ServiceArea::query()->count())->toBe(1)
        ->and($area->fresh()->name)->toBe('Kawo')
        ->and($area->fresh()->is_active)->toBeTrue();
});

test('admins can add, rename and deactivate areas when editing is on', function () {
    config(['kens.areas_editable' => true]);

    $this->actingAs($this->admin)->post(route('admin.areas.store'), ['name' => '  Tudun   Wada ']);
    $area = ServiceArea::query()->where('name', 'Tudun Wada')->firstOrFail();

    $this->put(route('admin.areas.update', $area), ['name' => 'Tudun Wada North'])->assertSessionHasNoErrors();
    expect($area->fresh()->name)->toBe('Tudun Wada North');

    $this->post(route('admin.areas.toggle', $area));
    expect($area->fresh()->is_active)->toBeFalse();

    $this->post(route('admin.areas.toggle', $area));
    expect($area->fresh()->is_active)->toBeTrue();
});

test('area names are unique', function () {
    config(['kens.areas_editable' => true]);
    ServiceArea::factory()->create(['name' => 'Kawo']);

    $this->actingAs($this->admin)->post(route('admin.areas.store'), ['name' => 'Kawo'])
        ->assertSessionHasErrors('name');
});

test('areas show how many submitted inspections they have', function () {
    $area = ServiceArea::factory()->create(['name' => 'Kawo']);
    Inspection::factory()->submitted()->inArea($area)->count(2)->create();
    Inspection::factory()->complete()->inArea($area)->create(); // draft: not counted

    $this->actingAs($this->admin)->get(route('admin.areas.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('areas.0.inspectionsTotal', 2)
            ->where('areas.0.inspectionsThisMonth', 2));
});
