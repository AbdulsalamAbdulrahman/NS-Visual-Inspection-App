<?php

declare(strict_types=1);

use App\Enums\CableColour;
use App\Enums\CircuitCondition;
use App\Enums\CircuitDescription;
use App\Enums\FuseType;
use App\Models\Inspection;
use App\Models\InspectionCircuit;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->contractor = User::factory()->contractor()->create();
});

test('starting an assigned job turns it into a draft dated today and opens the form', function () {
    $job = Inspection::factory()->assigned(User::factory()->rep()->create(), ServiceArea::factory()->create())
        ->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)
        ->post(route('inspections.start', $job))
        ->assertRedirect(route('inspections.edit', $job));

    $job->refresh();
    expect($job->isDraft())->toBeTrue()
        ->and($job->inspection_date->isToday())->toBeTrue();
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

test('ceiling fan circuits and insulated cut-out fuses save (client additions)', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), [
            'fuse_type' => 'insulated',
            'circuits' => [['uuid' => (string) Str::uuid(), 'description' => 'ceiling_fan', 'rating_a' => 6, 'conductor_mm2' => 1.5, 'condition' => 'satisfactory']],
        ])
        ->assertOk();

    $inspection->refresh();
    expect($inspection->fuse_type)->toBe(FuseType::Insulated)
        ->and($inspection->circuits()->sole()->description)->toBe(CircuitDescription::CeilingFan);
});

test('circuits save their earthing cable size and colours; insulation is PVC only (LECAN)', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create();
    $circuit = ['uuid' => (string) Str::uuid(), 'description' => 'lighting', 'earth_conductor_mm2' => 1.5, 'live_colour' => 'red', 'neutral_colour' => 'black', 'earth_colour' => 'green_yellow', 'condition' => 'satisfactory'];

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $inspection->uuid), ['cable_insulation' => 'pvc', 'circuits' => [$circuit]])
        ->assertOk();

    $saved = $inspection->fresh()->circuits()->sole();
    expect($saved->earth_conductor_mm2)->toBe(1.5)
        ->and($saved->live_colour)->toBe(CableColour::Red)
        ->and($saved->earth_colour)->toBe(CableColour::GreenYellow)
        ->and($inspection->fresh()->cable_insulation)->toBe('pvc');

    $this->putJson(route('inspections.draft', $inspection->uuid), ['cable_insulation' => 'xlpe', 'circuits' => [[...$circuit, 'live_colour' => 'purple']]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cable_insulation', 'circuits.0.live_colour']);
});

test('a failed save keeps the old signature file and leaves no new one behind', function () {
    $inspection = Inspection::factory()->forContractor($this->contractor)->create(['signature_path' => 'inspections/old/signature-old.png']);
    Storage::disk('local')->put('inspections/old/signature-old.png', "\x89PNG old");
    $png = 'data:image/png;base64,'.base64_encode(file_get_contents(public_path('apple-touch-icon.png')));
    InspectionCircuit::creating(fn () => throw new RuntimeException('database refused the circuit'));

    $this->withoutExceptionHandling();
    expect(fn () => $this->actingAs($this->contractor)->putJson(route('inspections.draft', $inspection->uuid), [
        'signature' => $png,
        'circuits' => [['uuid' => (string) Str::uuid(), 'description' => 'lighting']],
    ]))->toThrow(RuntimeException::class);

    expect($inspection->fresh()->signature_path)->toBe('inspections/old/signature-old.png')
        ->and(Storage::disk('local')->files("inspections/{$inspection->uuid}"))->toBe([]);
    Storage::disk('local')->assertExists('inspections/old/signature-old.png');
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

test('saving to an unknown uuid returns 404: drafts only start from assigned jobs', function () {
    $uuid = (string) Str::uuid();

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $uuid), ['owner_name' => 'Hauwa Sani'])
        ->assertNotFound();

    expect(Inspection::query()->where('uuid', $uuid)->exists())->toBeFalse();
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

    $job = Inspection::factory()->assigned($rep, ServiceArea::factory()->create())->create();

    $this->actingAs($rep)->post(route('inspections.start', $job))->assertForbidden();
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

test('the form flags customer details filled in by a rep', function () {
    $job = Inspection::factory()->assigned(User::factory()->rep()->create(), ServiceArea::factory()->create())
        ->forContractor($this->contractor)->create(['status' => 'draft', 'owner_phone' => '08031234567']);

    $this->actingAs($this->contractor)->get(route('inspections.edit', $job))
        ->assertInertia(fn (Assert $page) => $page
            ->where('inspection.from_rep', true)
            ->where('inspection.owner_phone', '08031234567'));
});

test('draft saves leave the rep\'s customer details alone but keep the contractor\'s readings', function () {
    $job = Inspection::factory()->assigned(User::factory()->rep()->create(), ServiceArea::factory()->create())
        ->forContractor($this->contractor)->create(['status' => 'draft', 'owner_name' => 'Amina Sule', 'form74_no' => 'F74/KD/2026/08841']);

    $this->actingAs($this->contractor)
        ->putJson(route('inspections.draft', $job->uuid), ['owner_name' => 'Someone Else', 'form74_no' => 'F74/KD/2026/99999', 'connection_type' => 'three_phase'])
        ->assertOk();

    $job->refresh();
    expect($job->owner_name)->toBe('Amina Sule')
        ->and($job->form74_no)->toBe('F74/KD/2026/08841')
        ->and($job->connection_type?->value)->toBe('three_phase');
});

test('refused and rejected draft saves are logged with field names but no values', function () {
    Log::spy();
    $submitted = Inspection::factory()->forContractor($this->contractor)->submitted()->create();
    $draft = Inspection::factory()->forContractor($this->contractor)->create();
    $unknown = (string) Str::uuid();

    $this->actingAs($this->contractor)->putJson(route('inspections.draft', $submitted->uuid), ['owner_name' => 'Changed'])->assertForbidden();
    $this->putJson(route('inspections.draft', $unknown), ['owner_name' => 'Changed'])->assertNotFound();
    $this->putJson(route('inspections.draft', $draft->uuid), ['purpose' => 'castle'])->assertUnprocessable();

    Log::shouldHaveReceived('warning')->with('Draft save refused', Mockery::subset(['inspection' => $submitted->uuid, 'reason' => 'forbidden', 'status' => 'submitted']))->once();
    Log::shouldHaveReceived('warning')->with('Draft save refused', Mockery::subset(['inspection' => $unknown, 'reason' => 'not_found']))->once();
    Log::shouldHaveReceived('warning')->with('Draft save rejected', ['user' => $this->contractor->id, 'inspection' => $draft->uuid, 'fields' => ['purpose']])->once();
});
