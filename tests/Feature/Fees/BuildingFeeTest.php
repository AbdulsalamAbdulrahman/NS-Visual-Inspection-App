<?php

declare(strict_types=1);

use App\Enums\BuildingType;
use App\Enums\PropertyPurpose;
use App\Models\FeeSchedule;
use App\Models\Inspection;
use App\Models\User;
use App\Support\Form74Range;
use App\Support\InspectionChecklist;
use App\Support\InspectionFee;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->travelTo('2026-10-13 10:00:00');
    FeeSchedule::factory()->create(['amount_kobo' => 1_500_000, 'effective_from' => '2026-07-01']);
    FeeSchedule::factory()->create(['building_type' => BuildingType::BlockOfFlats, 'amount_kobo' => 300_000, 'effective_from' => '2026-10-01']);
});

test('form 74 ranges count structures and explain mistakes', function (?string $first, ?string $last, int|string $expected) {
    $result = Form74Range::count($first, $last);

    is_int($expected) ? expect($result)->toBe($expected) : expect($result)->toContain($expected);
})->with([
    'single' => ['F74/KD/2026/08841', null, 1],
    'full last number' => ['F74/KD/2026/08841', 'F74/KD/2026/08860', 20],
    'digits only' => ['F74/KD/2026/08841', '08860', 20],
    'other prefix' => ['F74/KD/2026/08841', 'F74/ZR/2026/08860', 'same prefix'],
    'backwards' => ['F74/KD/2026/08841', '08840', 'after the first'],
    'too many' => ['F74/KD/2026/00001', '01000', 'more than 500'],
]);

test('an estate pays its building type fee per structure', function () {
    $inspection = Inspection::factory()->complete()->create([
        'purpose' => PropertyPurpose::Residential,
        'building_type' => BuildingType::BlockOfFlats,
        'structure_count' => 20,
    ]);

    expect(InspectionFee::quote($inspection))->toMatchArray([
        'unitKobo' => 300_000,
        'count' => 20,
        'totalKobo' => 6_000_000,
        'amount' => '₦60,000.00',
        'breakdown' => '20 structures × ₦3,000.00',
    ]);
});

test('types without their own fee use the default', function () {
    $inspection = Inspection::factory()->complete()->create(['purpose' => PropertyPurpose::Commercial, 'building_type' => BuildingType::Plaza]);

    expect(InspectionFee::quote($inspection)['totalKobo'])->toBe(1_500_000);
});

test('saving a draft counts the structures in the range', function () {
    $contractor = User::factory()->contractor()->create();
    $inspection = Inspection::factory()->forContractor($contractor)->create();

    $this->actingAs($contractor)->putJson(route('inspections.draft', $inspection->uuid), [
        'form74_no' => 'F74/KD/2026/08841',
        'form74_last_no' => '08860',
        'purpose' => 'residential',
        'building_type' => 'block_of_flats',
    ])->assertOk();

    expect($inspection->fresh()->structure_count)->toBe(20);

    $this->putJson(route('inspections.draft', $inspection->uuid), ['form74_last_no' => null])->assertOk();
    expect($inspection->fresh()->structure_count)->toBe(1);
});

test('the building type must match the use of property and the range must be valid', function () {
    $inspection = Inspection::factory()->complete()->create(['purpose' => PropertyPurpose::Industrial, 'building_type' => BuildingType::Duplex]);
    expect(collect(InspectionChecklist::missing($inspection))->pluck('key')->all())->toBe(['building_type']);

    $inspection->update(['building_type' => BuildingType::SmallFactory, 'form74_last_no' => 'F74/ZR/2026/1']);
    expect(collect(InspectionChecklist::missing($inspection->fresh()))->pluck('key')->all())->toBe(['form74_last_no']);
});

test('the pay page shows the per-structure breakdown', function () {
    $contractor = User::factory()->contractor()->create();
    $inspection = Inspection::factory()->complete()->forContractor($contractor)->create([
        'purpose' => PropertyPurpose::Residential,
        'building_type' => BuildingType::BlockOfFlats,
        'form74_no' => 'F74/KD/2026/08841',
        'form74_last_no' => '08860',
        'structure_count' => 20,
    ]);

    $this->actingAs($contractor)->get(route('inspections.pay', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('fee.amount', '₦60,000.00')
            ->where('fee.breakdown', '20 structures × ₦3,000.00')
            ->where('inspection.form74No', 'F74/KD/2026/08841 – 08860')
            ->where('inspection.buildingType', 'Block of flats'));
});

test('the form gets the fee table to price any building type offline', function () {
    $contractor = User::factory()->contractor()->create();
    $inspection = Inspection::factory()->forContractor($contractor)->create();

    $this->actingAs($contractor)->get(route('inspections.edit', $inspection))
        ->assertInertia(fn (Assert $page) => $page
            ->where('fees.default', 1_500_000)
            ->where('fees.types', ['block_of_flats' => 300_000])
            ->has('options.buildingType', count(BuildingType::cases())));
});
