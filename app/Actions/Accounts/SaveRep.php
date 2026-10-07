<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use App\Support\TemporaryPassword;
use Illuminate\Support\Facades\DB;

class SaveRep
{
    /**
     * Create a rep (Invited, login details emailed) or update an existing one.
     *
     * @param  array{name: string, email: string, phone: string|null, service_area_ids: list<int>}  $data
     */
    public function handle(array $data, ?User $rep = null): User
    {
        $password = $rep === null ? TemporaryPassword::generate() : null;

        $rep = DB::transaction(function () use ($data, $rep, $password): User {
            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ];

            if ($rep === null) {
                $rep = User::query()->create([
                    ...$attributes,
                    'role' => Role::Rep,
                    'status' => UserStatus::Invited,
                    'must_change_password' => true,
                    'password' => $password,
                ]);
            } else {
                $rep->update($attributes);
            }

            $rep->serviceAreas()->sync($data['service_area_ids']);

            return $rep;
        });

        if ($password !== null) {
            $rep->notify(new LoginDetails($password));
        }

        return $rep;
    }
}
