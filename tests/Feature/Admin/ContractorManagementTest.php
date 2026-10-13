<?php

declare(strict_types=1);

use App\Enums\NemsaCategory;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

function contractorPayload(array $overrides = []): array
{
    return [
        'name' => 'Musa Garba Abubakar',
        'email' => 'M.Abubakar@GarbaPower.ng',
        'phone' => '0805 300 9921',
        'nemsa_category' => 'corporate',
        'nemsa_reg_no' => 'cc/ce1f/pr/08634',
        'coren_no' => '',
        'firm_name' => 'Garba Power Ltd',
        'engineer_name' => 'Engr. Halima Garba',
        'engineer_reg_no' => '',
        ...$overrides,
    ];
}

test('admins see the contractors list with a summary', function () {
    User::factory()->contractor()->count(2)->create();
    User::factory()->contractor()->suspended()->create();

    $this->actingAs($this->admin)->get(route('admin.contractors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Contractors')
            ->has('contractors.data', 3)
            ->where('summary.total', 3)
            ->where('summary.suspended', 1));
});

test('the list can be searched by NEMSA number', function () {
    $match = User::factory()->contractor()->create();
    $match->contractorProfile->update(['nemsa_reg_no' => 'NEMSA/A/2023/0142']);
    User::factory()->contractor()->count(3)->create();

    $this->actingAs($this->admin)->get(route('admin.contractors.index', ['search' => '2023/0142']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('contractors.data', 1)
            ->where('contractors.data.0.uuid', $match->uuid));
});

test('creating a contractor makes an invited account and emails login details', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload())
        ->assertRedirect(route('admin.contractors.index'));

    $contractor = User::query()->where('email', 'm.abubakar@garbapower.ng')->firstOrFail();

    expect($contractor->isContractor())->toBeTrue()
        ->and($contractor->status)->toBe(UserStatus::Invited)
        ->and($contractor->must_change_password)->toBeTrue()
        ->and($contractor->contractorProfile->nemsa_category)->toBe(NemsaCategory::Corporate)
        ->and($contractor->contractorProfile->nemsa_reg_no)->toBe('CC/CE1F/PR/08634')
        ->and($contractor->contractorProfile->engineer_name)->toBe('Engr. Halima Garba')
        ->and($contractor->contractorProfile->coren_no)->toBeNull();

    Notification::assertSentTo($contractor, LoginDetails::class, function (LoginDetails $mail) use ($contractor) {
        return Hash::check($mail->temporaryPassword, $contractor->password);
    });
});

test('corporate contractors need a firm name', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload(['firm_name' => '']))
        ->assertSessionHasErrors(['firm_name' => 'Required for Corporate contractors']);
});

test('the registration number must look like a NEMSA number and be unique', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload(['nemsa_reg_no' => '12345']))
        ->assertSessionHasErrors('nemsa_reg_no');

    $existing = User::factory()->contractor()->create();
    $existing->contractorProfile->update(['nemsa_reg_no' => 'CC/CE1F/PR/08634']);

    $this->post(route('admin.contractors.store'), contractorPayload())
        ->assertSessionHasErrors('nemsa_reg_no');
});

test('a deleted account keeps its email reserved', function () {
    $old = User::factory()->contractor()->create(['email' => 'm.abubakar@garbapower.ng']);
    $old->delete();

    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload())
        ->assertSessionHasErrors('email');
});

test('contractor details can be edited', function () {
    $contractor = User::factory()->contractor()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.contractors.update', $contractor), contractorPayload(['email' => $contractor->email, 'nemsa_category' => 'cat_b', 'nemsa_reg_no' => 'nemsa b 2024 0377', 'firm_name' => '']))
        ->assertSessionHasNoErrors();

    expect($contractor->fresh()->contractorProfile)
        ->nemsa_category->toBe(NemsaCategory::CatB)
        ->nemsa_reg_no->toBe('NEMSAB20240377')
        ->firm_name->toBeNull()
        ->engineer_name->toBeNull();
});

test('non-admins cannot manage contractors', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($rep)->get(route('admin.contractors.index'))->assertForbidden();
    $this->actingAs($rep)->post(route('admin.contractors.store'), contractorPayload())->assertForbidden();
});

test('individual registration numbers take letters and numbers only', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload(['nemsa_category' => 'cat_a', 'nemsa_reg_no' => 'NEMSA/A/2023/0142', 'firm_name' => '']))
        ->assertSessionHasErrors(['nemsa_reg_no' => 'Letters and numbers only, no spaces or slashes.']);

    $this->post(route('admin.contractors.store'), contractorPayload(['nemsa_category' => 'cat_a', 'nemsa_reg_no' => 'NEMSAA20230142', 'firm_name' => '']))
        ->assertSessionHasNoErrors();
});

test('corporate registration numbers follow the CC/CE1F/PR/08634 format', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload(['nemsa_reg_no' => 'CCCE1FPR08634']))
        ->assertSessionHasErrors(['nemsa_reg_no' => 'Use the corporate format, e.g. CC/CE1F/PR/08634.']);
});

test('corporate contractors must name their electrical engineer or technologist', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.contractors.store'), contractorPayload(['engineer_name' => '']))
        ->assertSessionHasErrors(['engineer_name' => 'Required for Corporate contractors']);
});
