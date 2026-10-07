<?php

declare(strict_types=1);

use App\Models\Inspection;
use App\Support\InspectionChecklist;

test('a complete inspection has nothing missing', function () {
    $inspection = Inspection::factory()->complete()->create();

    expect(InspectionChecklist::missing($inspection))->toBe([]);
});

test('missing items point at the step to fix', function () {
    $inspection = Inspection::factory()->complete()->create(['form74_no' => null, 'signature_path' => null]);
    $inspection->attachments()->where('type', 'photo')->delete();

    $keys = collect(InspectionChecklist::missing($inspection->fresh()))->mapWithKeys(fn ($m) => [$m['key'] => $m['step']]);

    expect($keys->all())->toBe(['form74_no' => 1, 'photos' => 7, 'signature' => 8]);
});

test('standard answers are only required for equipment that was seen', function () {
    $inspection = Inspection::factory()->complete()->create(['db_seen' => true, 'db_standard' => null]);

    expect(collect(InspectionChecklist::missing($inspection))->pluck('key'))->toContain('db');

    $inspection->update(['db_seen' => false]);

    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('key'))->not->toContain('db');
});

test('a deactivated service area must be changed before payment', function () {
    $inspection = Inspection::factory()->complete()->create();
    $inspection->serviceArea->update(['is_active' => false]);

    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('key'))->toContain('service_area_id');
});

test('out-of-range readings never block submission', function () {
    $inspection = Inspection::factory()->complete()->create(['earth_resistance_ohm' => 9.5, 'earth_electrode_ft' => 3]);

    expect(InspectionChecklist::isComplete($inspection))->toBeTrue();
});
