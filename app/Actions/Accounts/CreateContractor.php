<?php

declare(strict_types=1);

namespace App\Actions\Accounts;

use App\Enums\NemsaCategory;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\LoginDetails;
use App\Support\TemporaryPassword;
use Illuminate\Support\Facades\DB;

class CreateContractor
{
    /**
     * Create an Invited contractor with their licence and email a temporary password.
     *
     * @param  array{name: string, email: string, phone: string|null, nemsa_category: string, nemsa_reg_no: string, coren_no: string|null, firm_name: string|null}  $data
     */
    public function handle(array $data): User
    {
        $password = TemporaryPassword::generate();

        $user = DB::transaction(function () use ($data, $password): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => Role::Contractor,
                'status' => UserStatus::Invited,
                'must_change_password' => true,
                'password' => $password,
            ]);

            $user->contractorProfile()->create([
                'nemsa_category' => NemsaCategory::from($data['nemsa_category']),
                'nemsa_reg_no' => $data['nemsa_reg_no'],
                'coren_no' => $data['coren_no'],
                'firm_name' => $data['firm_name'],
            ]);

            return $user;
        });

        $user->notify(new LoginDetails($password));

        return $user;
    }
}
