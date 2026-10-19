<?php

declare(strict_types=1);

use App\Enums\CircuitDescription;
use App\Http\Resources\InspectionReportResource;
use App\Models\Inspection;
use App\Models\User;
use App\Support\InspectionChecklist;
use Illuminate\Support\Str;

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

test('the house front view photo is required', function () {
    $inspection = Inspection::factory()->complete()->create();
    $inspection->attachments()->where('type', 'front_view')->delete();

    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('label', 'key')->all())
        ->toBe(['front_view' => 'House front view photo']);
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

test('a breaker or fuse marked not available needs no details', function () {
    $inspection = Inspection::factory()->complete()->create([
        'cb_available' => false, 'cb_make' => null, 'cb_rated_a' => null, 'cb_standard' => null, 'poles' => null,
        'fuse_available' => false, 'fuse_rated_a' => null, 'fuse_type' => null, 'fuse_standard' => null, 'fuse_poles' => null,
    ]);

    expect(InspectionChecklist::missing($inspection))->toBe([]);
});

test('an available breaker or fuse needs its rating, standard and poles; make is optional', function () {
    $inspection = Inspection::factory()->complete()->create(['cb_make' => null, 'fuse_type' => null]);
    expect(collect(InspectionChecklist::missing($inspection))->pluck('key')->all())->toBe(['fuse']);

    $inspection->update(['fuse_type' => 'polymer', 'cb_available' => null]);
    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('key')->all())->toBe(['cb']);
});

test('new mains items are required only when seen', function () {
    $inspection = Inspection::factory()->complete()->create([
        'changeover_seen' => true, 'changeover_standard' => true, 'changeover_phase' => null, 'changeover_rated_a' => null,
        'db_main_breaker_seen' => false, 'db_main_breaker_rated_a' => null, 'db_main_breaker_poles' => null, 'db_main_breaker_standard' => null,
        'main_switch_poles' => null,
    ]);

    expect(collect(InspectionChecklist::missing($inspection))->pluck('key')->all())->toBe(['changeover', 'main_switch']);
});

test('poles are limited to 4 when saving a draft', function () {
    $contractor = User::factory()->contractor()->create();
    $inspection = Inspection::factory()->forContractor($contractor)->create();

    $this->actingAs($contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), ['poles' => 5, 'fuse_poles' => 4, 'fuse_type' => 'porcelain'])
        ->assertJsonValidationErrors('poles');

    $this->putJson(route('inspections.draft', $inspection->uuid), ['poles' => 4, 'fuse_type' => 'porcelain', 'circuits' => [
        ['uuid' => (string) Str::uuid(), 'description' => 'ceiling_fan', 'rating_a' => 6, 'conductor_mm2' => 1.5, 'condition' => 'satisfactory'],
    ]])->assertOk();

    expect($inspection->fresh()->circuits->first()->description)->toBe(CircuitDescription::CeilingFan);
});

test('each circuit needs its earthing cable size and live, neutral and earth colours', function () {
    $inspection = Inspection::factory()->complete()->create();
    $inspection->circuits()->update(['neutral_colour' => null]);

    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('label'))->toContain('Circuit C1 details');

    $inspection->circuits()->update(['neutral_colour' => 'black', 'earth_conductor_mm2' => null]);

    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('label'))->toContain('Circuit C1 details');
});

test('earth resistance is flagged above 5 Ω', function (float $ohms, bool $flagged) {
    $inspection = Inspection::factory()->complete()->create(['earth_resistance_ohm' => $ohms]);

    expect((new InspectionReportResource($inspection))->toArray(request())['b1']['warnings']['resistance'])->toBe($flagged);
})->with([
    '4.8 Ω' => [4.8, false],
    '5 Ω' => [5.0, false],
    '5.2 Ω' => [5.2, true],
]);
