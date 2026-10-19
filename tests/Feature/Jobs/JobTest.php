<?php

declare(strict_types=1);

use App\Enums\InspectionStatus;
use App\Enums\NigerianState;
use App\Models\ContractorProfile;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\JobAssigned;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->barnawa = ServiceArea::factory()->create(['name' => 'Barnawa', 'state' => NigerianState::Kaduna]);
    $this->sokoto = ServiceArea::factory()->create(['name' => 'Sokoto North', 'state' => NigerianState::Sokoto]);
    $this->zaria = ServiceArea::factory()->create(['name' => 'Zaria', 'state' => NigerianState::Kaduna]);
    $this->rep = User::factory()->rep([$this->barnawa, $this->sokoto])->create();
});

function contractorIn(NigerianState $state): User
{
    $contractor = User::factory()->contractor()->create();
    $contractor->contractorProfile->update(['state' => $state]);

    return $contractor;
}

/**
 * @return array<string, mixed>
 */
function jobPayload(array $overrides = []): array
{
    return [
        'owner_name' => 'Amina Sule',
        'property_address' => '14 Ahmadu Bello Way, Barnawa',
        'owner_phone' => '+234 803 123 4567',
        'owner_email' => 'amina.sule@example.com',
        'service_area_id' => test()->barnawa->id,
        'purpose' => 'residential',
        'building_type' => 'duplex',
        'form74_no' => 'f74/kd/2026/08841',
        'form74_last_no' => '',
        ...$overrides,
    ];
}

test('a rep creates a job assigned to a contractor in the area\'s state and the contractor is emailed', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid]))
        ->assertRedirect(route('rep.jobs.index'));

    $job = Inspection::query()->sole();
    expect($job->status)->toBe(InspectionStatus::Assigned)
        ->and($job->contractor_id)->toBe($contractor->id)
        ->and($job->assigned_by)->toBe($this->rep->id)
        ->and($job->owner_phone)->toBe('08031234567')
        ->and($job->form74_no)->toBe('F74/KD/2026/08841')
        ->and($job->inspection_date)->toBeNull();

    Notification::assertSentTo($contractor, JobAssigned::class);
    Notification::assertSentTimes(JobAssigned::class, 2);
});

test('the owner\'s email is required and the owner is told who will inspect', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'owner_email' => '']))
        ->assertSessionHasErrors('owner_email');

    $this->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'owner_email' => 'Amina@Example.com']))
        ->assertRedirect();

    Notification::assertSentOnDemand(JobAssigned::class, fn ($n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'amina@example.com');
});

test('an estate job counts its structures from the Form 74 range', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'form74_no' => 'F74/KD/2026/09001', 'form74_last_no' => '09020']))
        ->assertRedirect();

    expect(Inspection::query()->sole()->structure_count)->toBe(20);
});

test('a contractor in another state cannot be assigned', function () {
    $sokotoContractor = contractorIn(NigerianState::Sokoto);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $sokotoContractor->uuid]))
        ->assertSessionHasErrors(['contractor' => 'Choose an active contractor with a valid licence and complete documents who works in Kaduna State.']);

    expect(Inspection::query()->count())->toBe(0);
});

test('a suspended contractor cannot be assigned', function () {
    $contractor = contractorIn(NigerianState::Kaduna);
    $contractor->update(['status' => 'suspended']);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid]))
        ->assertSessionHasErrors('contractor');
});

test('a rep cannot create a job in an area office that is not theirs', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'service_area_id' => $this->zaria->id]))
        ->assertSessionHasErrors(['service_area_id' => 'Choose one of your area offices.']);
});

test('job validation reports the phone, building type and Form 74 range', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'owner_phone' => '0803']))
        ->assertSessionHasErrors(['owner_phone' => 'Enter an 11-digit phone number, e.g. 08031234567.']);

    $this->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'building_type' => 'shop']))
        ->assertSessionHasErrors(['building_type' => 'Choose a building description under Residential.']);

    $this->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid, 'form74_last_no' => '08800']))
        ->assertSessionHasErrors(['form74_last_no' => 'The last Form 74 number must be after the first.']);
});

test('only reps create jobs', function () {
    $contractor = contractorIn(NigerianState::Kaduna);

    $this->actingAs($contractor)->post(route('rep.jobs.store'), jobPayload(['contractor' => $contractor->uuid]))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('rep.jobs.create'))->assertForbidden();
});

test('the job form lists only active contractors in the rep\'s states', function () {
    $kaduna = contractorIn(NigerianState::Kaduna);
    contractorIn(NigerianState::Kebbi);

    $this->actingAs($this->rep)->get(route('rep.jobs.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rep/JobForm')
            ->has('areas', 2)
            ->has('contractors', 1)
            ->where('contractors.0.uuid', $kaduna->uuid));
});

test('reassigning a job that has not started emails the new contractor', function () {
    $first = contractorIn(NigerianState::Kaduna);
    $second = contractorIn(NigerianState::Kaduna);
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($first)->create();

    $this->actingAs($this->rep)
        ->put(route('rep.jobs.update', $job), jobPayload(['contractor' => $second->uuid, 'owner_name' => 'Amina S. Sule']))
        ->assertRedirect(route('rep.jobs.index'));

    expect($job->fresh())
        ->contractor_id->toBe($second->id)
        ->owner_name->toBe('Amina S. Sule');
    Notification::assertSentTo($second, JobAssigned::class);
    Notification::assertNotSentTo($first, JobAssigned::class);
    Notification::assertSentOnDemand(JobAssigned::class, fn ($n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'amina.sule@example.com');
});

test('a job cannot be changed or cancelled once the contractor has started it', function () {
    $contractor = contractorIn(NigerianState::Kaduna);
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($contractor)->create(['status' => InspectionStatus::Draft]);

    $this->actingAs($this->rep)->put(route('rep.jobs.update', $job), jobPayload(['contractor' => $contractor->uuid]))->assertForbidden();
    $this->delete(route('rep.jobs.destroy', $job))->assertForbidden();

    expect($job->fresh())->not->toBeNull();
});

test('a rep cancels a job that has not started', function () {
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor(contractorIn(NigerianState::Kaduna))->create();

    $this->actingAs($this->rep)->delete(route('rep.jobs.destroy', $job))->assertRedirect(route('rep.jobs.index'));

    expect(Inspection::query()->count())->toBe(0);
});

test('another area\'s rep cannot change a job', function () {
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor(contractorIn(NigerianState::Kaduna))->create();
    $other = User::factory()->rep([$this->zaria])->create();

    $this->actingAs($other)->get(route('rep.jobs.edit', $job))->assertForbidden();
});

test('the job list shows jobs in the rep\'s areas with their progress', function () {
    $contractor = contractorIn(NigerianState::Kaduna);
    Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($contractor)->create();
    Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($contractor)->create(['status' => InspectionStatus::Draft]);
    Inspection::factory()->assigned(User::factory()->rep([$this->zaria])->create(), $this->zaria)->forContractor($contractor)->create();
    Inspection::factory()->inArea($this->barnawa)->create(); // contractor's own legacy draft, not a job

    $this->actingAs($this->rep)->get(route('rep.jobs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rep/Jobs')
            ->where('counts', ['all' => 2, 'not_started' => 1, 'in_progress' => 1, 'submitted' => 0])
            ->has('jobs.data', 2));

    $this->get(route('rep.jobs.index', ['progress' => 'not_started']))
        ->assertInertia(fn (Assert $page) => $page->has('jobs.data', 1)->where('jobs.data.0.progress', 'not_started'));
});

test('the contractor home lists jobs assigned to them, not to others', function () {
    $contractor = contractorIn(NigerianState::Kaduna);
    Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($contractor)->create(['owner_name' => 'Amina Sule']);
    Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor(contractorIn(NigerianState::Kaduna))->create();

    $this->actingAs($contractor)->get(route('inspections.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('assigned', 1)
            ->where('assigned.0.ownerName', 'Amina Sule')
            ->has('drafts', 0));
});

test('a contractor cannot start another contractor\'s job', function () {
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor(contractorIn(NigerianState::Kaduna))->create();

    $this->actingAs(contractorIn(NigerianState::Kaduna))->post(route('inspections.start', $job))->assertForbidden();

    expect($job->fresh()->status)->toBe(InspectionStatus::Assigned);
});

test('a job that has not started cannot be opened in the form', function () {
    $contractor = contractorIn(NigerianState::Kaduna);
    $job = Inspection::factory()->assigned($this->rep, $this->barnawa)->forContractor($contractor)->create();

    $this->actingAs($contractor)->get(route('inspections.edit', $job))->assertRedirect(route('inspections.index'));
    $this->putJson(route('inspections.draft', $job->uuid), ['connection_type' => 'single_phase'])->assertForbidden();
});

test('a contractor\'s state is required when the admin saves them', function () {
    $contractor = User::factory()->contractor()->create();
    $profile = $contractor->contractorProfile;

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.contractors.update', $contractor), [
            'name' => $contractor->name,
            'email' => $contractor->email,
            'nemsa_category' => $profile->nemsa_category->value,
            'nemsa_reg_no' => $profile->nemsa_reg_no,
        ])
        ->assertSessionHasErrors('state');

    $this->put(route('admin.contractors.update', $contractor), [
        'name' => $contractor->name,
        'email' => $contractor->email,
        'nemsa_category' => $profile->nemsa_category->value,
        'nemsa_reg_no' => $profile->nemsa_reg_no,
        'state' => 'sokoto',
    ])->assertSessionHasNoErrors();

    expect(ContractorProfile::query()->sole()->state)->toBe(NigerianState::Sokoto);
});
