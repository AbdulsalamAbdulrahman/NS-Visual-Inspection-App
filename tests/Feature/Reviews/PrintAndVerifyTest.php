<?php

declare(strict_types=1);

use App\Http\Controllers\VerifyController;
use App\Models\Inspection;
use App\Models\ServiceArea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->area = ServiceArea::factory()->create(['name' => 'Barnawa']);
    $this->other = ServiceArea::factory()->create(['name' => 'Zaria']);
    $this->contractor = User::factory()->contractor()->create();
    $this->rep = User::factory()->rep([$this->area])->create();
    $this->admin = User::factory()->admin()->create();
});

test('the full report prints for every allowed role, without payment details', function () {
    $inspection = Inspection::factory()->submitted()->forContractor($this->contractor)->inArea($this->area)->create();

    foreach ([$this->admin, $this->rep, $this->contractor] as $user) {
        $this->actingAs($user)
            ->get(route('inspections.print', $inspection))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('print/Report')
                ->where('report.ticketNo', $inspection->ticket_no)
                ->missing('report.payment')
                ->where('certificateUrl', null)
                ->has('qr'));
    }
});

test('print access follows role, area and ownership', function () {
    $foreign = Inspection::factory()->submitted()->inArea($this->other)->create();
    $draft = Inspection::factory()->complete()->forContractor($this->contractor)->inArea($this->area)->create();

    $this->actingAs($this->rep)->get(route('inspections.print', $foreign))->assertForbidden();
    $this->actingAs($this->contractor)->get(route('inspections.print', $foreign))->assertForbidden();
    $this->actingAs($this->contractor)->get(route('inspections.print', $draft))->assertForbidden();
    $this->get(route('inspections.print', $foreign))->assertForbidden();
});

test('the certificate exists only once approved', function () {
    $pending = Inspection::factory()->submitted()->forContractor($this->contractor)->inArea($this->area)->create();
    $returned = Inspection::factory()->changesRequested()->forContractor($this->contractor)->inArea($this->area)->create();

    foreach ([$this->admin, $this->rep, $this->contractor] as $user) {
        $this->actingAs($user)->get(route('inspections.certificate', $pending))->assertForbidden();
        $this->actingAs($user)->get(route('inspections.certificate', $returned))->assertForbidden();
    }
});

test('approved certificates show the snapshot signatory and verify link', function () {
    $inspection = Inspection::factory()->approved()->forContractor($this->contractor)->inArea($this->area)->create();
    Storage::disk('local')->put($inspection->signatory_signature_path, 'png-bytes');

    $this->actingAs($this->rep)
        ->get(route('inspections.certificate', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('print/Certificate')
            ->where('certificate.certificateNo', $inspection->ticket_no)
            ->where('certificate.signatory.name', 'Engr. Hauwa Abdullahi')
            ->where('certificate.area', 'Barnawa')
            ->where('certificate.verifyUrl', fn (string $url) => str_ends_with($url, '/verify/'.$inspection->ticket_no)));

    $this->get(route('inspections.certificate.signature', $inspection))->assertOk();

    $this->actingAs(User::factory()->rep([$this->other])->create())
        ->get(route('inspections.certificate.signature', $inspection))
        ->assertForbidden();
});

test('the public verify page shows status and a masked owner only', function () {
    $inspection = Inspection::factory()->approved()->inArea($this->area)->create([
        'owner_name' => 'Alhaji Musa Ibrahim',
        'property_address' => '14 Ahmadu Bello Way, Kaduna',
        'inspector_name' => 'Engr. Yusuf Bello',
    ]);

    $this->get(route('verify', $inspection->ticket_no))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verify')
            ->where('result', [
                'status' => 'approved',
                'submittedAt' => $inspection->submitted_at->format('d M Y'),
                'approvedAt' => $inspection->approved_at->format('d M Y'),
                'area' => 'Barnawa',
                'contractor' => 'Engr. Yusuf Bello',
                'owner' => 'Alhaji M. I.',
            ]))
        ->assertDontSee('Ahmadu Bello Way')
        ->assertDontSee('Musa Ibrahim');
});

test('verify reports pending and unknown tickets honestly', function () {
    $pending = Inspection::factory()->submitted()->create();
    $draft = Inspection::factory()->complete()->create(['ticket_no' => 'KE-NSD-2026-999999']);

    $this->get(route('verify', strtolower($pending->ticket_no)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('result.status', 'pending')->where('result.approvedAt', null));

    $this->get(route('verify', 'KE-NSD-2026-999999'))->assertNotFound();
    $this->get(route('verify', 'nonsense'))->assertNotFound()->assertInertia(fn (Assert $page) => $page->where('result', null));
});

test('verify is rate limited', function () {
    foreach (range(1, 30) as $i) {
        $this->get(route('verify', 'KE-NSD-2026-000001'));
    }

    $this->get(route('verify', 'KE-NSD-2026-000001'))->assertTooManyRequests();
});

test('owner names are masked to initials', function (?string $name, ?string $masked) {
    expect(VerifyController::maskName($name))->toBe($masked);
})->with([
    ['Alhaji Musa Ibrahim', 'Alhaji M. I.'],
    ['Musa', 'M.'],
    ['  hauwa   sani ', 'hauwa S.'],
    ['', null],
    [null, null],
]);
