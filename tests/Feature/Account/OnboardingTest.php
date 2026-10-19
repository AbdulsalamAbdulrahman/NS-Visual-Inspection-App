<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\ContractorProfile;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->contractor = User::factory()->state(['role' => Role::Contractor])
        ->has(ContractorProfile::factory()->withoutDocuments(), 'contractorProfile')
        ->create();
});

test('a contractor without documents can only reach the onboarding page', function () {
    $job = Inspection::factory()->forContractor($this->contractor)->create();

    $this->actingAs($this->contractor)->get(route('home'))->assertRedirect(route('onboarding'));
    $this->get(route('inspections.index'))->assertRedirect(route('onboarding'));
    $this->get(route('profile.show'))->assertRedirect(route('onboarding'));
    $this->putJson(route('inspections.draft', $job->uuid), [])->assertStatus(423);

    $this->get(route('onboarding'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('contractor/Onboarding')
        ->where('profile.documents.status.missing', ['Licence copy', 'Licence issue date', 'Licence expiry date', 'Passport photograph', 'Signature']));
});

test('the contractor adds their documents from the onboarding page, then continues', function () {
    $this->actingAs($this->contractor)->post(route('profile.documents.store'), [
        'licence' => UploadedFile::fake()->create('licence.pdf', 300, 'application/pdf'),
        'passport' => UploadedFile::fake()->image('me.jpg'),
        'signature' => UploadedFile::fake()->image('sign.png'),
    ])->assertSessionHasNoErrors();
    $this->get(route('profile.documents.show', 'passport'))->assertOk();
    $this->get(route('inspections.index'))->assertRedirect(route('onboarding'));

    $this->post(route('profile.documents.store'), ['licence_issued_at' => today()->subYear()->toDateString(), 'licence_expires_at' => today()->addYear()->toDateString()])
        ->assertSessionHasNoErrors();

    $this->get(route('inspections.index'))->assertOk();
    $this->get(route('onboarding'))->assertRedirect(route('home'));
});

test('reps cannot assign a contractor who has not completed their documents', function () {
    $area = ServiceArea::factory()->create(['state' => $this->contractor->contractorProfile->state]);

    $this->actingAs(User::factory()->rep([$area])->create())->get(route('rep.jobs.create'))
        ->assertInertia(fn (Assert $page) => $page->where('contractors', []));
});
