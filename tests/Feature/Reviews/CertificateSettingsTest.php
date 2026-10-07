<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
});

test('admins set the certificate signatory with a signature image', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.certificate.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/CertificateSettings')->where('complete', false));

    $this->post(route('admin.certificate.update'), ['name' => 'Engr. Hauwa Abdullahi', 'title' => 'Head, NSD'])
        ->assertSessionHasErrors('signature');

    $this->post(route('admin.certificate.update'), [
        'name' => 'Engr. Hauwa Abdullahi',
        'title' => 'Head, New Service Department',
        'signature' => UploadedFile::fake()->image('sig.png', 600, 200),
    ])->assertSessionHasNoErrors();

    $signatory = Setting::signatory();
    expect($signatory)->not->toBeNull()
        ->and($signatory['name'])->toBe('Engr. Hauwa Abdullahi');
    Storage::disk('local')->assertExists($signatory['signature_path']);

    $this->get(route('admin.certificate.signature'))->assertOk();
});

test('replacing the signature removes the old file; name-only edits keep it', function () {
    $this->actingAs($this->admin)->post(route('admin.certificate.update'), [
        'name' => 'A', 'title' => 'B', 'signature' => UploadedFile::fake()->image('one.png', 600, 200),
    ]);
    $first = Setting::get(Setting::SIGNATORY_SIGNATURE);

    $this->post(route('admin.certificate.update'), ['name' => 'Renamed', 'title' => 'B'])->assertSessionHasNoErrors();
    expect(Setting::get(Setting::SIGNATORY_SIGNATURE))->toBe($first);

    $this->post(route('admin.certificate.update'), [
        'name' => 'Renamed', 'title' => 'B', 'signature' => UploadedFile::fake()->image('two.png', 600, 200),
    ])->assertSessionHasNoErrors();

    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists(Setting::get(Setting::SIGNATORY_SIGNATURE));
});

test('signature images must be real, reasonably sized images', function () {
    $this->actingAs($this->admin)->post(route('admin.certificate.update'), [
        'name' => 'A', 'title' => 'B', 'signature' => UploadedFile::fake()->create('sig.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('signature');

    $this->post(route('admin.certificate.update'), [
        'name' => 'A', 'title' => 'B', 'signature' => UploadedFile::fake()->image('tiny.png', 50, 20),
    ])->assertSessionHasErrors('signature');
});

test('only admins reach the signatory settings', function () {
    $this->actingAs(User::factory()->rep()->create())->get(route('admin.certificate.edit'))->assertForbidden();
    $this->actingAs(User::factory()->contractor()->create())->get(route('admin.certificate.signature'))->assertForbidden();
});
