<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use App\Notifications\QueuedResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

test('resending login details issues a new temporary password', function () {
    $contractor = User::factory()->contractor()->create();
    $oldHash = $contractor->password;

    $this->actingAs($this->admin)->post(route('admin.accounts.resend-login', $contractor))->assertRedirect();

    $contractor->refresh();

    expect($contractor->password)->not->toBe($oldHash)
        ->and($contractor->must_change_password)->toBeTrue()
        ->and($contractor->status)->toBe(UserStatus::Invited);

    Notification::assertSentTo($contractor, LoginDetails::class, fn (LoginDetails $n) => $n->resent);
});

test('admins can send a password reset link', function () {
    $rep = User::factory()->rep()->create();

    $this->actingAs($this->admin)->post(route('admin.accounts.reset-link', $rep))->assertRedirect();

    Notification::assertSentTo($rep, QueuedResetPassword::class);
});

test('accounts can be suspended and reactivated', function () {
    $contractor = User::factory()->contractor()->create();

    $this->actingAs($this->admin)->post(route('admin.accounts.suspend', $contractor));
    expect($contractor->fresh()->status)->toBe(UserStatus::Suspended);

    $this->post(route('admin.accounts.reactivate', $contractor));
    expect($contractor->fresh()->status)->toBe(UserStatus::Active);
});

test('reactivating someone who never set a password puts them back to invited', function () {
    $contractor = User::factory()->contractor()->invited()->suspended()->create();

    $this->actingAs($this->admin)->post(route('admin.accounts.reactivate', $contractor));

    expect($contractor->fresh()->status)->toBe(UserStatus::Invited);
});

test('deleting soft-deletes the account and signs it out', function () {
    $contractor = User::factory()->contractor()->create();
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $contractor->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $contractor))->assertRedirect();

    $this->assertSoftDeleted($contractor);
    expect(DB::table('sessions')->where('user_id', $contractor->id)->exists())->toBeFalse();
});

test('admins cannot act on other admins', function () {
    $other = User::factory()->admin()->create();

    $this->actingAs($this->admin)->post(route('admin.accounts.suspend', $other))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $other))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('admin.accounts.destroy', $this->admin))->assertForbidden();
});
