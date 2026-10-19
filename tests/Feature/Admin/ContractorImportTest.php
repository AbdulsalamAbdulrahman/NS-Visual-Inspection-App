<?php

declare(strict_types=1);

use App\Enums\NemsaCategory;
use App\Enums\NigerianState;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

const CONTRACTOR_CSV_HEADER = 'name,email,phone,resident_state,nemsa_category,nemsa_reg_no,coren_no,firm_name,engineer_name,engineer_reg_no,licence_issued_on,licence_expires_on';

function contractorsCsv(string ...$rows): UploadedFile
{
    return csvWithHeader(CONTRACTOR_CSV_HEADER, ...$rows);
}

function csvWithHeader(string $header, string ...$rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent('contractors.csv', implode("\n", [$header, ...$rows])."\n");
}

test('the template has the registration columns', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.contractors.template'));

    $response->assertOk()->assertDownload('kens-contractors-template.csv');
    expect(strtok($response->streamedContent(), "\n"))->toBe(CONTRACTOR_CSV_HEADER);
});

test('an upload is previewed with each row\'s problems and nobody is registered', function () {
    User::factory()->contractor()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post(route('admin.contractors.import.upload'), ['file' => contractorsCsv(
            'Aisha Lawal,aisha@example.com,08062201145,kaduna,Cat B,NEMSAB20220388,,,,',
            'Musa Garba,taken@example.com,,Lagos,Cat Z,NEMSA/B/1,,,,',
            'Danladi Ltd,info@danladi.example.com,,Kaduna,Corporate,CC/CE1F/PR/08634,,,,',
            'Aisha Again,AISHA@example.com,,Kaduna,A,NEMSAA1,,,,',
        )])
        ->assertRedirect(route('admin.contractors.import'));

    expect(User::query()->role(Role::Contractor)->count())->toBe(1);

    $this->get(route('admin.contractors.import'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ContractorImport')
            ->has('rows', 4)
            ->where('rows.0.errors', [])
            ->where('rows.0.state', 'Kaduna')
            ->where('rows.1.errors', fn ($errors) => collect($errors)->contains('An account with this email already exists (it may have been deleted).')
                && collect($errors)->contains('State “Lagos” isn\'t one of Kaduna, Kebbi, Sokoto or Zamfara.')
                && collect($errors)->contains('NEMSA category “Cat Z” isn\'t recognised (Cat A, Cat B, Cat C or Corporate).'))
            ->where('rows.2.errors', ['Firm name is required for Corporate contractors.', 'Engineer name is required for Corporate contractors.'])
            ->where('rows.3.errors', ['Email is also on row 2.']));
});

test('confirming registers the valid rows with their state and emails login details', function () {
    $this->actingAs($this->admin)->post(route('admin.contractors.import.upload'), ['file' => contractorsCsv(
        'Aisha Lawal,aisha@example.com,08062201145,Kaduna,Cat B,NEMSAB20220388,,,,',
        'Danladi Electrical Services,info@danladi.example.com,,sokoto,Corporate,CC/CE1F/PR/08634,R.09931,Danladi Electrical Services Ltd,Engr. Ibrahim Danladi,',
        'No Category,nocat@example.com,,Kaduna,,NEMSAB1,,,,',
    )]);

    $this->post(route('admin.contractors.import.confirm'))
        ->assertRedirect(route('admin.contractors.index'))
        ->assertInertiaFlash('toast.message', '2 contractors registered and emailed their login details, 1 skipped.');

    $danladi = User::query()->where('email', 'info@danladi.example.com')->sole();
    expect($danladi->status)->toBe(UserStatus::Invited)
        ->and($danladi->contractorProfile->state)->toBe(NigerianState::Sokoto)
        ->and($danladi->contractorProfile->nemsa_category)->toBe(NemsaCategory::Corporate)
        ->and($danladi->contractorProfile->engineer_name)->toBe('Engr. Ibrahim Danladi')
        ->and(User::query()->where('email', 'nocat@example.com')->exists())->toBeFalse();
    Notification::assertSentTimes(LoginDetails::class, 2);
});

test('licence dates are read in the usual formats and saved on the profile', function () {
    $this->travelTo('2026-10-19 09:00:00');

    $this->actingAs($this->admin)->post(route('admin.contractors.import.upload'), ['file' => contractorsCsv(
        'Aisha Lawal,aisha@example.com,,Kaduna,Cat B,NEMSAB1,,,,,15/01/2025,14/01/2028',
        'Bala Musa,bala@example.com,,Kaduna,Cat B,NEMSAB2,,,,,2025-03-01,3 Mar 2027',
        'No Dates,nodates@example.com,,Kaduna,Cat B,NEMSAB3,,,,,,',
        'Bad Date,bad@example.com,,Kaduna,Cat B,NEMSAB4,,,,,31/02/2025,soon',
        'Future,future@example.com,,Kaduna,Cat B,NEMSAB5,,,,,01/01/2027,01/01/2029',
        'Expired,expired@example.com,,Kaduna,Cat B,NEMSAB6,,,,,01/01/2020,02/10/2026',
    )]);

    $this->get(route('admin.contractors.import'))->assertInertia(fn (Assert $page) => $page
        ->where('rows.0.errors', [])
        ->where('rows.0.expires', '14 Jan 2028')
        ->where('rows.1.errors', [])
        ->where('rows.2.errors', [])
        ->where('rows.2.expires', null)
        ->where('rows.3.errors', ['Licence issue date “31/02/2025” isn\'t a date (use DD/MM/YYYY).', 'Licence expiry date “soon” isn\'t a date (use DD/MM/YYYY).'])
        ->where('rows.4.errors', ['The licence issue date can’t be in the future.'])
        ->where('rows.5.errors', ['The licence expired on 2 Oct 2026.']));

    $this->post(route('admin.contractors.import.confirm'))->assertRedirect(route('admin.contractors.index'));

    $aisha = User::query()->where('email', 'aisha@example.com')->sole()->contractorProfile;
    expect($aisha->licence_issued_at->toDateString())->toBe('2025-01-15')
        ->and($aisha->licence_expires_at->toDateString())->toBe('2028-01-14')
        ->and(User::query()->where('email', 'nodates@example.com')->sole()->contractorProfile->licence_expires_at)->toBeNull();
});

test('files made from the first template, or with the client\'s own headers, still import', function () {
    $this->actingAs($this->admin)->post(route('admin.contractors.import.upload'), ['file' => csvWithHeader(
        'name,email,phone,state,nemsa_category,nemsa_reg_no,coren_no,firm_name,engineer_name,engineer_reg_no',
        'Aisha Lawal,aisha@example.com,,Kaduna,Cat B,NEMSAB1,,,,',
    )]);
    $this->get(route('admin.contractors.import'))->assertInertia(fn (Assert $page) => $page->where('rows.0.errors', [])->where('rows.0.state', 'Kaduna'));

    $this->post(route('admin.contractors.import.upload'), ['file' => csvWithHeader(
        'name,email,phone,resident state,nemsa_category,nemsa_reg_no,coren_no,firm_name,engineer_name,liscence Issuance date,liscence Expiry date,pasport photogragh,liscence photogragh,signature',
        'Bala Musa,bala@example.com,,Sokoto,Cat A,NEMSAA1,,,,15/01/2025,14/01/2028,,,',
    )])->assertSessionHasNoErrors();
    $this->get(route('admin.contractors.import'))->assertInertia(fn (Assert $page) => $page
        ->where('rows.0.errors', [])
        ->where('rows.0.state', 'Sokoto')
        ->where('rows.0.expires', '14 Jan 2028'));
});

test('only admins register contractors in bulk', function () {
    $this->actingAs(User::factory()->rep()->create())
        ->post(route('admin.contractors.import.upload'), ['file' => contractorsCsv('A,a@example.com,,Kaduna,Cat A,NEMSAA1,,,,')])
        ->assertForbidden();
});
