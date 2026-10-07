<?php

declare(strict_types=1);

use App\Enums\CircuitCondition;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->contractor = User::factory()->contractor()->create();
});

test('a contractor can start a draft with a phone-generated uuid', function () {
    $uuid = (string) Str::uuid();

    $this->actingAs($this->contractor)
        ->post(route('inspections.store'), ['uuid' => $uuid])
        ->assertRedirect(route('inspections.edit', $uuid));

    $inspection = Inspection::query()->where('uuid', $uuid)->firstOrFail();

    expect($inspection->contractor_id)->toBe($this->contractor->id)
        ->and($inspection->isDraft())->toBeTrue()
        ->and($inspection->inspection_date->isToday())->toBeTrue();
});

test('the form opens on the saved step with options and the inspector profile', function () {
    ServiceArea::factory()->create(['name' => 'Kawo']);
    $inspection = Inspection::factory()->forContractor($this->contractor)->create(['current_step' => 5]);

    $this->actingAs($this->contractor)->get(route('inspections.edit', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/InspectionForm')
            ->where('step', 5)
            ->where('inspection.uuid', $inspection->uuid)
            ->has('areas', 1)
            ->has('options.circuitCondition', 4)
            ->where('inspector.name', $this->contractor->name));
});

test('saving a draft accepts incomplete data and partial updates', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), [
            'current_step' => 2,
            'owner_name' => 'Alhaji Musa Ibrahim',
            'earth_resistance_ohm' => 2.6,
            'earth_pit' => true,
            'declaration_accepted' => true,
        ])
        ->assertOk()
        ->assertJsonStructure(['uuid', 'saved_at']);

    $inspection->refresh();

    expect($inspection->owner_name)->toBe('Alhaji Musa Ibrahim')
        ->and($inspection->earth_resistance_ohm)->toBe(2.6)
        ->and($inspection->earth_pit)->toBeTrue()
        ->and($inspection->current_step)->toBe(2)
        ->and($inspection->declaration_accepted_at)->not->toBeNull()
        ->and($inspection->form74_no)->toBeNull();
});

test('circuits are replaced as a whole and numbered by position', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();
    $circuits = [
        ['uuid' => (string) Str::uuid(), 'description' => 'lighting', 'rating_a' => 10, 'conductor_mm2' => 1.5, 'condition' => 'satisfactory'],
        ['uuid' => (string) Str::uuid(), 'description' => 'other', 'description_other' => 'Perimeter lights', 'condition' => 'improvement_required'],
    ];

    $this->actingAs($this->contractor)->putJson(route('inspections.draft', $inspection->uuid), ['circuits' => $circuits])->assertOk();
    $this->putJson(route('inspections.draft', $inspection->uuid), ['circuits' => [$circuits[1]]])->assertOk();

    $saved = $inspection->fresh()->circuits;

    expect($saved)->toHaveCount(1)
        ->and($saved[0]->position)->toBe(1)
        ->and($saved[0]->label())->toBe('C1')
        ->and($saved[0]->condition)->toBe(CircuitCondition::ImprovementRequired)
        ->and($saved[0]->descriptionLabel())->toBe('Other · Perimeter lights');
});

test('a drawn signature is stored as a private PNG', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();
    $png = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('apple-touch-icon.png')));

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), ['signature' => $png])
        ->assertOk()
        ->assertJsonPath('signature_url', fn ($url) => str_contains($url, "/inspections/{$inspection->uuid}/signature"));

    $path = $inspection->fresh()->signature_path;
    Storage::disk('local')->assertExists($path);
    expect($path)->toStartWith("inspections/{$inspection->uuid}/");

    $this->putJson(route('inspections.draft', $inspection->uuid), ['signature' => null])->assertOk();
    Storage::disk('local')->assertMissing($path);
    expect($inspection->fresh()->signature_path)->toBeNull();
});

test('saving to an unknown uuid creates the draft (offline-started drafts)', function () {
    $uuid = (string) Str::uuid();

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $uuid), ['owner_name' => 'Hauwa Sani'])
        ->assertOk();

    expect(Inspection::query()->where('uuid', $uuid)->value('owner_name'))->toBe('Hauwa Sani');
});

test('draft values are range-checked even though they are optional', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), ['gps_lat' => 120, 'gps_lng' => 7, 'poles' => 20, 'purpose' => 'castle'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['gps_lat', 'poles', 'purpose']);
});

test('a contractor cannot see or change another contractor\'s inspection', function () {
    $theirs = Inspection::factory()->create();

    $this->actingAs($this->contractor)->get(route('inspections.edit', $theirs))->assertForbidden();
    $this->putJson(route('inspections.draft', $theirs->uuid), ['owner_name' => 'Hijack'])->assertForbidden();
    $this->get(route('inspections.signature', $theirs))->assertForbidden();

    expect($theirs->fresh()->owner_name)->toBeNull();
});

test('a submitted inspection cannot be edited', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->submitted()->create(['owner_name' => 'Original']);

    $this->actingAs($this->contractor)->putJson(route('inspections.draft', $inspection->uuid), ['owner_name' => 'Changed'])->assertForbidden();
    $this->get(route('inspections.edit', $inspection))->assertRedirect(route('inspections.show', $inspection));

    expect($inspection->fresh()->owner_name)->toBe('Original');
});

test('only contractors use the form', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)->post(route('inspections.store'))->assertForbidden();
    $this->actingAs($rep)->putJson(route('inspections.draft', (string) Str::uuid()), [])->assertForbidden();
});

test('the home page lists drafts and submitted inspections separately', function () {
    Inspection::factory()->forContractor($this->contractor)->count(2)->create();
    Inspection::factory()->forContractor($this->contractor)->submitted()->create();
    Inspection::factory()->submitted()->create(); // someone else's

    $this->actingAs($this->contractor)->get(route('inspections.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('contractor/Home')
            ->has('drafts', 2)
            ->has('submitted.data', 1)
            ->where('submittedTotal', 1));
});
