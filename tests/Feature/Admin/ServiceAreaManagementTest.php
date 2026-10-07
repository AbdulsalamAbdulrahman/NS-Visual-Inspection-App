<?php

declare(strict_types=1);

use App\Models\ServiceArea;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('admins can add, rename and deactivate areas', function () {
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
    ServiceArea::factory()->create(['name' => 'Kawo']);

    $this->actingAs($this->admin)->post(route('admin.areas.store'), ['name' => 'Kawo'])
        ->assertSessionHasErrors('name');
});
