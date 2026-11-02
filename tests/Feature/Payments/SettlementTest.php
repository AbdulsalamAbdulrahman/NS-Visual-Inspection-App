<?php

declare(strict_types=1);

use App\Enums\BuildingType;
use App\Enums\PaymentStatus;
use App\Enums\Premises;
use App\Enums\SettlementParty as Party;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\PaymentSplit;
use App\Models\SettlementParty;
use App\Models\User;
use App\Support\InspectionFee;
use App\Support\Settlement;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Payments\MonnifyFake;

test('the seven shares add up to the fee to the kobo', function (int $fee) {
    $inspector = User::factory()->contractor()->create();
    $rows = collect(Settlement::split($fee, $inspector));

    expect($rows)->toHaveCount(7)
        ->and($rows->sum('amount_kobo'))->toBe($fee)
        ->and($rows->firstWhere('party', Party::Inspector)['contractor_id'])->toBe($inspector->id);
})->with([3_000_000, 1_000_001, 333]);

test('a ₦30,000 fee gives the inspector ₦12,000', function () {
    $rows = collect(Settlement::split(3_000_000, User::factory()->contractor()->create()));

    expect($rows->firstWhere('party', Party::Inspector)['amount_kobo'])->toBe(1_200_000)
        ->and($rows->firstWhere('party', Party::AdminHq)['amount_kobo'])->toBe(600_000);
});

test('flats and plazas use the sheet column for their size', function (BuildingType $type, int $count, Premises $premises) {
    expect(Premises::forBuilding($type, $count))->toBe($premises);
})->with([
    [BuildingType::TwoBedroomFlat, 4, Premises::StandAlone],
    [BuildingType::BlockOfFlats, 5, Premises::Multiple],
    [BuildingType::BlockOfFlats, 16, Premises::Large],
    [BuildingType::Shop, 5, Premises::StandAlone],
    [BuildingType::Plaza, 2, Premises::Multiple],
    [BuildingType::Duplex, 1, Premises::StandAlone],
]);

test('the checkout charge is capped and sits on top of the fee', function () {
    config(['services.monnify.charge_percent' => 1.5, 'services.monnify.charge_cap_kobo' => 200_000]);

    expect(InspectionFee::charge(3_000_000))->toBe(45_000)
        ->and(InspectionFee::charge(100_000_000))->toBe(200_000);
});

test('payment is refused when a party has no sub-account', function () {
    Settlement::monnifyConfig(Settlement::split(3_000_000, User::factory()->contractor()->create()));
})->throws(ValidationException::class);

test('the main account takes no split and the others are sent as amounts', function () {
    SettlementParty::query()->update(['sub_account_code' => 'MFY_SUB_X']);

    $config = Settlement::monnifyConfig(Settlement::split(3_000_000, User::factory()->contractor()->create()));

    expect($config)->toHaveCount(6)->and($config[0]['splitAmount'])->toEqual(12000.0);
});

test('a bank account is confirmed and gets a sub-account', function () {
    MonnifyFake::configure();
    Http::fake([
        '*/auth/login' => Http::response(['requestSuccessful' => true, 'responseBody' => ['accessToken' => 't', 'expiresIn' => 3600]]),
        '*/api/v1/banks' => Http::response(['requestSuccessful' => true, 'responseBody' => [['code' => '058', 'name' => 'GTBank']]]),
        '*/account/validate*' => Http::response(['requestSuccessful' => true, 'responseBody' => ['accountName' => 'AMINA BELLO']]),
        '*/api/v1/sub-accounts' => Http::response(['requestSuccessful' => true, 'responseBody' => [['subAccountCode' => 'MFY_SUB_1']]]),
    ]);

    $user = User::factory()->contractor()->create();

    $this->actingAs($user)->post(route('profile.documents.bank'), ['bank_code' => '058', 'account_number' => '0123456789'])
        ->assertSessionHasNoErrors();

    expect($user->contractorProfile->fresh())
        ->account_name->toBe('AMINA BELLO')
        ->sub_account_code->toBe('MFY_SUB_1');
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sub-accounts'));
});

test('ratios must total 100 and only admins reach the portal', function () {
    $admin = User::factory()->admin()->create();
    $percentages = SettlementParty::query()->pluck('percentage_bp', 'id')->map(fn (int $bp) => $bp / 100)->all();

    $this->actingAs($admin)->put(route('admin.settlements.ratios'), ['percentages' => $percentages])->assertSessionHasNoErrors();

    $first = array_key_first($percentages);
    $percentages[$first] = 50;
    $this->put(route('admin.settlements.ratios'), ['percentages' => $percentages])->assertSessionHasErrors('percentages');

    $this->actingAs(User::factory()->contractor()->create())->get(route('admin.settlements.index'))->assertForbidden();
});

test('an inspector sees only their own earnings', function () {
    $mine = User::factory()->contractor()->create();
    $payment = Payment::factory()->for(Inspection::factory()->submitted())->create(['contractor_id' => $mine->id, 'status' => PaymentStatus::Paid, 'paid_at' => now()]);
    PaymentSplit::query()->create(['payment_id' => $payment->id, 'party' => Party::Inspector, 'contractor_id' => $mine->id, 'percentage_bp' => 4000, 'amount_kobo' => 1_200_000]);
    PaymentSplit::query()->create(['payment_id' => $payment->id, 'party' => Party::Inspector, 'contractor_id' => User::factory()->contractor()->create()->id, 'percentage_bp' => 4000, 'amount_kobo' => 999]);

    $this->actingAs($mine)->get(route('earnings'))
        ->assertInertia(fn ($page) => $page->component('contractor/Earnings')->has('rows', 1)->where('rows.0.amount', '₦12,000.00'));
});
