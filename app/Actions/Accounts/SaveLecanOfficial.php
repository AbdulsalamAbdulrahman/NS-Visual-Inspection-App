<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use App\Support\TemporaryPassword;

class SaveLecanOfficial
{
    /**
     * Create a LECAN official (Invited, login details emailed) or update one.
     *
     * @param  array{name: string, email: string, phone: string|null, lecan_state: string}  $data
     */
    public function handle(array $data, ?User $official = null): User
    {
        if ($official !== null) {
            $official->update($data);

            return $official;
        }

        $password = TemporaryPassword::generate();

        $official = User::query()->create([
            ...$data,
            'role' => Role::Lecan,
            'status' => UserStatus::Invited,
            'must_change_password' => true,
            'password' => $password,
        ]);

        $official->notify(new LoginDetails($password));

        return $official;
    }
}
