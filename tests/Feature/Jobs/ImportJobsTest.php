<?php

declare(strict_types=1);

use App\Enums\InspectionStatus;
use App\Enums\NigerianState;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\JobAssigned;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->barnawa = ServiceArea::factory()->create(['name' => 'Barnawa', 'state' => NigerianState::Kaduna]);
    $this->rep = User::factory()->rep([$this->barnawa])->create();
});

const JOB_CSV_HEADER = 'owner_name,property_address,phone,email,area_office,purpose,building_type,form74_first,form74_last,contractor_email';

function jobsCsv(string ...$rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent('jobs.csv', implode("\n", [JOB_CSV_HEADER, ...$rows])."\n");
}

test('the template has the import columns', function () {
    $response = $this->actingAs($this->rep)->get(route('rep.jobs.template'));

    $response->assertOk()->assertDownload('kens-jobs-template.csv');
    expect(strtok($response->streamedContent(), "\n"))->toBe(JOB_CSV_HEADER);
});

test('an upload is previewed with each row\'s problems and nothing is saved', function () {
    $contractor = User::factory()->contractor()->create(['email' => 'y.bello@bellopower.ng']);

    $this->actingAs($this->rep)
        ->post(route('rep.jobs.import.upload'), ['file' => jobsCsv(
            'Amina Sule,"14 Ahmadu Bello Way",08031234567,amina@example.com,barnawa,Residential,Duplex,F74/KD/2026/08841,,Y.Bello@bellopower.ng',
            'Musa Garba,"3 Kachia Road",0803,,Kawo,Residential,Castle,F74/KD/2026/08842,,nobody@example.com',
        )])
        ->assertRedirect(route('rep.jobs.import'));

    expect(Inspection::query()->count())->toBe(0);

    $this->get(route('rep.jobs.import'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('rep/JobImport')
            ->has('rows', 2)
            ->where('rows.0.line', 2)
            ->where('rows.0.contractor', $contractor->name)
            ->where('rows.0.errors', [])
            ->where('rows.1.line', 3)
            ->where('rows.1.errors', fn ($errors) => collect($errors)->contains('“Kawo” isn\'t one of your area offices.')
                && collect($errors)->contains('Building type “Castle” isn\'t recognised.')
                && collect($errors)->contains('Enter an 11-digit phone number, e.g. 08031234567.')
                && collect($errors)->contains('Enter the owner’s email: they get the receipt and certificate.')));
});

test('confirming creates the valid rows as assigned jobs and skips the rest', function () {
    $contractor = User::factory()->contractor()->create(['email' => 'y.bello@bellopower.ng']);

    $this->actingAs($this->rep)->post(route('rep.jobs.import.upload'), ['file' => jobsCsv(
        'Amina Sule,"14 Ahmadu Bello Way",08031234567,amina@example.com,Barnawa,Residential,Duplex,F74/KD/2026/08841,,y.bello@bellopower.ng',
        'Estate Ltd,"Plot 3",08059876543,estates@example.com,Barnawa,Residential,Block of flats,F74/KD/2026/09001,F74/KD/2026/09020,y.bello@bellopower.ng',
        'Bad Row,"Somewhere",08059876543,,Barnawa,Residential,Duplex,,,y.bello@bellopower.ng',
    )]);

    $this->post(route('rep.jobs.import.confirm'))
        ->assertRedirect(route('rep.jobs.index'))
        ->assertInertiaFlash('toast.message', '2 jobs created, 1 skipped.');

    $jobs = Inspection::query()->orderBy('id')->get();
    expect($jobs)->toHaveCount(2)
        ->and($jobs->pluck('status')->unique()->all())->toBe([InspectionStatus::Assigned])
        ->and($jobs[1]->structure_count)->toBe(20);
    Notification::assertSentTo($contractor, JobAssigned::class, 2);
    Notification::assertSentOnDemandTimes(JobAssigned::class, 2);
});

test('a file without the template columns is refused', function () {
    $this->actingAs($this->rep)
        ->post(route('rep.jobs.import.upload'), ['file' => UploadedFile::fake()->createWithContent('jobs.csv', "name,address\nAmina,Kaduna\n")])
        ->assertSessionHasErrors('file');

    $this->get(route('rep.jobs.import'))->assertInertia(fn (Assert $page) => $page->has('rows', 0));
});
