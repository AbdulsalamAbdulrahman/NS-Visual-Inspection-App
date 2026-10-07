<?php

declare(strict_types=1);

use App\Models\ServiceArea;
use App\Models\User;
use App\Notifications\LoginDetails;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
    $this->areas = ServiceArea::factory()->count(4)->create();
});

test('creating a rep assigns their areas and emails login details', function () {
    $this->actingAs($this->admin)->post(route('admin.reps.store'), [
        'name' => 'Abubakar Shehu',
        'email' => 'a.shehu@kadunaelectric.com',
        'phone' => '0807 664 2210',
        'service_area_ids' => $this->areas->take(2)->pluck('id')->all(),
    ])->assertRedirect(route('admin.reps.index'));

    $rep = User::query()->where('email', 'a.shehu@kadunaelectric.com')->firstOrFail();

    expect($rep->isRep())->toBeTrue()
        ->and($rep->serviceAreas->pluck('id')->sort()->values()->all())
        ->toBe($this->areas->take(2)->pluck('id')->sort()->values()->all());

    Notification::assertSentTo($rep, LoginDetails::class);
});

test('a rep needs between one and three areas', function (int $count) {
    $this->actingAs($this->admin)->post(route('admin.reps.store'), [
        'name' => 'Abubakar Shehu',
        'email' => 'a.shehu@kadunaelectric.com',
        'service_area_ids' => $this->areas->take($count)->pluck('id')->all(),
    ])->assertSessionHasErrors('service_area_ids');
})->with([0, 4]);

test('inactive areas cannot be assigned', function () {
    $inactive = ServiceArea::factory()->inactive()->create();

    $this->actingAs($this->admin)->post(route('admin.reps.store'), [
        'name' => 'Abubakar Shehu',
        'email' => 'a.shehu@kadunaelectric.com',
        'service_area_ids' => [$inactive->id],
    ])->assertSessionHasErrors('service_area_ids.0');
});

test('editing a rep replaces their areas', function () {
    $rep = User::factory()->rep([$this->areas[0]])->create();

    $this->actingAs($this->admin)->put(route('admin.reps.update', $rep), [
        'name' => $rep->name,
        'email' => $rep->email,
        'service_area_ids' => [$this->areas[2]->id, $this->areas[3]->id],
    ])->assertSessionHasNoErrors();

    expect($rep->fresh()->serviceAreas->pluck('id')->all())
        ->toEqualCanonicalizing([$this->areas[2]->id, $this->areas[3]->id]);
});

test('the area picker shows who already covers each area', function () {
    User::factory()->rep([$this->areas[0]])->create(['name' => 'Grace Ayuba']);

    $this->actingAs($this->admin)->get(route('admin.reps.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Reps')
            ->where('areas', fn ($areas) => collect($areas)->firstWhere('id', $this->areas[0]->id)['reps'] === ['Grace Ayuba']));
});
