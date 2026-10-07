<?php

declare(strict_types=1);

use App\Enums\ConnectionType;
use App\Enums\PaymentStatus;
use App\Enums\PropertyPurpose;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\ServiceArea;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->barnawa = ServiceArea::factory()->create(['name' => 'Barnawa']);
    $this->kawo = ServiceArea::factory()->create(['name' => 'Kawo']);
});

function paidFor(Inspection $inspection, int $kobo = 1_500_000): Payment
{
    return Payment::factory()->forInspection($inspection)->create([
        'status' => PaymentStatus::Paid,
        'amount_kobo' => $kobo,
        'amount_paid_kobo' => $kobo,
        'transaction_reference' => 'MNFY|'.fake()->unique()->numerify('########'),
        'paid_at' => now(),
    ]);
}

test('the admin list shows every submitted inspection', function () {
    paidFor(Inspection::factory()->submitted()->inArea($this->barnawa)->create());
    Inspection::factory()->submitted()->inArea($this->kawo)->create();
    Inspection::factory()->complete()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Inspections')
            ->where('totalSubmitted', 2)
            ->has('inspections.data', 2)
            ->where('inspections.meta.total', 2)
            ->where('filters.count', 0));
});

test('admin filters combine area, dates and search', function () {
    $match = Inspection::factory()->submitted()->inArea($this->barnawa)->create([
        'owner_name' => 'Hauwa Sani',
        'purpose' => PropertyPurpose::cases()[0],
        'connection_type' => ConnectionType::cases()[0],
        'submitted_at' => now()->subDays(3),
    ]);
    Inspection::factory()->submitted()->inArea($this->kawo)->create(['owner_name' => 'Hauwa Kawo', 'submitted_at' => now()->subDays(3)]);
    Inspection::factory()->submitted()->inArea($this->barnawa)->create(['owner_name' => 'Hauwa Old', 'submitted_at' => now()->subMonths(2)]);

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.index', [
            'search' => 'Hauwa',
            'areas' => (string) $this->barnawa->id,
            'from' => now()->subWeek()->toDateString(),
            'to' => now()->toDateString(),
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('inspections.data', 1)
            ->where('inspections.data.0.uuid', $match->uuid)
            ->where('filters.areas', [$this->barnawa->id]));

    $this->get(route('admin.inspections.index', ['search' => $match->ticket_no]))
        ->assertInertia(fn (Assert $page) => $page->has('inspections.data', 1));

    $this->get(route('admin.inspections.index', ['purpose' => PropertyPurpose::cases()[0]->value, 'connection' => ConnectionType::cases()[0]->value, 'search' => 'Hauwa Sani']))
        ->assertInertia(fn (Assert $page) => $page->has('inspections.data', 1)->where('filters.count', 2));
});

test('the admin detail includes payment details', function () {
    $inspection = Inspection::factory()->submitted()->create();
    $payment = paidFor($inspection);

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.show', $inspection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/InspectionShow')
            ->where('report.payment.reference', $payment->transaction_reference)
            ->where('report.payment.amount', '₦15,000.00')
            ->has('report.attachments'));
});

test('drafts are not visible on the admin detail route', function () {
    $draft = Inspection::factory()->complete()->create();

    $this->actingAs($this->admin)->get(route('admin.inspections.show', $draft))->assertNotFound();
});

test('the CSV export streams the filtered rows newest first', function () {
    $older = Inspection::factory()->submitted()->inArea($this->barnawa)->create(['submitted_at' => now()->subDays(2), 'owner_name' => 'Older, Owner']);
    $newer = Inspection::factory()->submitted()->inArea($this->barnawa)->create(['submitted_at' => now()->subDay()]);
    Inspection::factory()->submitted()->inArea($this->kawo)->create();
    paidFor($newer);

    $csv = $this->actingAs($this->admin)
        ->get(route('admin.inspections.export', ['areas' => $this->barnawa->id]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)->toStartWith("\u{FEFF}Ticket,");

    $rows = array_map('str_getcsv', array_slice(explode("\n", trim($csv)), 1));

    expect($rows)->toHaveCount(2)
        ->and($rows[0][0])->toBe($newer->ticket_no)
        ->and($rows[0][11])->toBe('₦15,000.00')
        ->and($rows[1][0])->toBe($older->ticket_no)
        ->and($rows[1][2])->toBe('Older, Owner');
});

test('the payments page filters by status and search with a monthly summary', function () {
    $inspection = Inspection::factory()->submitted()->create();
    paidFor($inspection);
    Payment::factory()->create(['status' => PaymentStatus::Failed]);
    Payment::factory()->create(['status' => PaymentStatus::Abandoned]);
    Payment::factory()->create(['status' => PaymentStatus::Pending]);

    $this->actingAs($this->admin)
        ->get(route('admin.payments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Payments')
            ->has('payments.data', 4)
            ->where('summary.collected', '₦15,000.00')
            ->where('summary.successful', 1)
            ->where('summary.failed', 1)
            ->where('summary.abandoned', 1));

    $this->get(route('admin.payments.index', ['status' => 'failed']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.status', 'failed')
            ->where('filters.status', 'failed'));

    $this->get(route('admin.payments.index', ['search' => $inspection->ticket_no]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.inspectionUuid', $inspection->uuid));
});

test('the payments export includes every matching attempt', function () {
    Payment::factory()->count(3)->create(['status' => PaymentStatus::Failed]);
    Payment::factory()->create(['status' => PaymentStatus::Pending]);

    $csv = $this->actingAs($this->admin)
        ->get(route('admin.payments.export', ['status' => 'failed']))
        ->assertOk()
        ->streamedContent();

    expect(array_slice(explode("\n", trim($csv)), 1))->toHaveCount(3);
});

test('the overview shows month KPIs, submissions by area and recent work', function () {
    $this->travelTo(now()->setDate(2026, 10, 15)->setTime(12, 0));

    paidFor(Inspection::factory()->submitted()->inArea($this->barnawa)->create(['submitted_at' => now()->subDay()]));
    Inspection::factory()->submitted()->inArea($this->barnawa)->create(['submitted_at' => now()->subDays(2)]);
    Inspection::factory()->submitted()->inArea($this->kawo)->create(['submitted_at' => now()->subMonth()]);
    Inspection::factory()->complete()->create();
    User::factory()->contractor()->suspended()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Overview')
            ->where('month.value', '2026-10')
            ->where('month.isCurrent', true)
            ->where('kpis.inspections', 2)
            ->where('kpis.inspectionsDelta', 1)
            ->where('kpis.revenueShort', '₦15K')
            ->where('kpis.drafts', 1)
            ->where('kpis.contractorsSuspended', 1)
            ->where('areas.0', ['name' => 'Barnawa', 'count' => 2])
            ->has('recent', 3)
            ->has('month.options', 12));

    $this->get(route('admin.overview', ['month' => '2026-09']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('month.isCurrent', false)
            ->where('kpis.inspections', 1)
            ->where('kpis.revenueShort', '₦0'));

    // Future or malformed months fall back to the current month.
    $this->get(route('admin.overview', ['month' => '2027-01']))
        ->assertInertia(fn (Assert $page) => $page->where('month.value', '2026-10'));
    $this->get(route('admin.overview', ['month' => '2026-13']))
        ->assertInertia(fn (Assert $page) => $page->where('month.value', '2026-10'));
});

test('the admin nav shares the submitted inspections count', function () {
    Inspection::factory()->submitted()->count(2)->create();
    Inspection::factory()->complete()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.inspections.index'))
        ->assertInertia(fn (Assert $page) => $page->where('adminNav.inspections', 2));
});
